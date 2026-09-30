<?php
namespace App\Models;
use CodeIgniter\Model;

class ClaimModel extends Model
{
    public function setClaimActivity($data)
    {
        $builder = $this->db->table('pfaces.pfaces_claim_activity');
        $builder->insert($data);
        return $this->db->insertId();
    }

	public function getSeamanAccount($ftcode, $id_no)
	{
		$str = "SELECT COALESCE(round((
					(date_part('year', COALESCE(s.date_of_death, NOW())) - date_part('year', date_of_birth)) * 12
					+ (date_part('month', COALESCE(s.date_of_death, NOW())) - date_part('month', date_of_birth))
					)::NUMERIC / 12.0 , 1), 0) AS age_in_years
					,a.acct_no
					,CASE COALESCE(a.acct_status, '')
						WHEN 'Y' THEN 'CLOSED'
						WHEN 'N' THEN 'OPEN'
						ELSE 'N/A'
					END AS acct_status					
				FROM pfaces.pfs_seaman_table s LEFT OUTER JOIN pfaces.pfs_seaman_account a
					ON (s.id_no = a.id_no and a.fund_tcode = ?) 
				WHERE s.id_no = ?";
		$q = $this->db->query($str, [$ftcode, $id_no]);
		$a_ret = $q->getResultArray();
		return $a_ret;
	}
// from pfaces
	public function checkHasFinancial($ftcode, $sid) 
	{
		$str = "SELECT COUNT(1) as cnt 
				FROM pfaces.pfs_claims_application
				WHERE fund_tcode = ?
				AND seaman_id = ?
				AND reason_claim_code = 'FINANCIAL'
				AND status_flag NOT IN ('".k_claim_status_not_started."','".k_claim_status_processing."')";
		$q = $this->db->query($str, [$ftcode, $sid]);
		$cnt = ($q->getRowArray()['cnt'] ?? 0);
		return ($cnt > 0);
	} // check_has_financial    

	public function getFundTypeDetails($ftcode)
	{
		$str = "SELECT fund_tcode
					,fund_tdesc
					,retirement_age
					,inactive_yrs
					,claim_death_yrs
					,currency
					,admin_by
					,check_by
					,buseiso 
				FROM pfaces.pfs_fund_type_table
				WHERE fund_tcode = ?";
		$q = $this->db->query($str, [$ftcode]);
		return ($q->getRowArray() ?? []);
	} // get_fund_type_details

	public function getSeamanFundAge($ftcode, $sid, $dtclm='')
	{
		if (strlen($dtclm ?? '') == 0)
			$dtclm = Date('Y-m-d');
		$a_where = [$dtclm, $dtclm,  $dtclm, $ftcode, $sid];
		$str = "SELECT ss.acct_no
						,MIN(ss.sign_on_date) as dtfirst
						,MAX(ss.sign_off_date) as dtlast
						,ROUND((
							(
							(EXTRACT(YEAR FROM AGE(MAX(ss.sign_off_date), MIN(ss.sign_on_date))) * 12)
							+ EXTRACT(MONTH FROM AGE(MAX(ss.sign_off_date), MIN(ss.sign_on_date)))
							) / 12.0
						), 1) AS age_fund_onoff
						,GREATEST(ROUND((
							(
							(EXTRACT(YEAR FROM AGE(NOW(), MAX(ss.sign_off_date))) * 12)
							+ EXTRACT(MONTH FROM AGE(NOW(), MAX(ss.sign_off_date)))
							) / 12.0
						), 1), 0) AS age_fund_tocurr
						,ROUND(EXTRACT(EPOCH FROM (MAX(ss.sign_off_date) - MIN(ss.sign_on_date))) / (365.25*24*60*60), 1) as age_of_fund
						,GREATEST(EXTRACT(DAY FROM (? - MIN(ss.sign_on_date))) / 360.0, 0) AS total_years_to_claim
						,GREATEST(EXTRACT(DAY FROM (? - MAX(ss.sign_off_date))) / 360.0, 0) AS total_years_inactive
						,SUM(EXTRACT(DAY FROM (ss.sign_off_date - ss.sign_on_date))) / 360.0 AS total_years_360
						,SUM(EXTRACT(EPOCH FROM (ss.sign_off_date - ss.sign_on_date)) / (365.25*24*60*60)) as actual_total_years
					FROM (SELECT acct_no  
							,sign_on_date
							,MAX(year_covered::text || RIGHT('0' || month_covered::text, 2)) as year_month
							,MAX(COALESCE(sign_off_date, DATE ?)) as sign_off_date
						FROM pfaces.pfs_seaman_sign_on_off_table
						WHERE fund_tcode = ?
							AND id_no = ?
						GROUP BY acct_no, sign_on_date) ss				
				GROUP BY ss.acct_no
				ORDER BY ss.acct_no";
		$q = $this->db->query($str, $a_where);
		$a_ret = $q->getResultArray();
		return $a_ret;
	}

	public function getClaimReasons($dtclm)
	{
		$a_where = [];
		$sql_cond = "";
		if (strlen($dtclm ?? '') > 0)
		{
			$sql_cond = " WHERE ? BETWEEN r.period_1 AND r.period_2 ";
			$a_where[] = $dtclm;
		}
		$str = "SELECT  r.reason_claim_code as reasoncode
						,r.reason_claim_desc as reasondesc
						,r.age_limit
					FROM pfaces.pfs_reason_claim r
					".$sql_cond." 
					ORDER BY r.reason_claim_desc";
		$q = $this->db->query($str, $a_where);
		$a_ret = $q->getResultArray();
		return $a_ret;
	}
}