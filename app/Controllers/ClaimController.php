<?php

namespace App\Controllers;

use App\Models\ApiLogModel;
use App\Models\SeamanModel;
use App\Models\ClaimModel;


class ClaimController extends BaseApiController
{
    public function set_claim_activity()
    {
        $username = $this->jwtUser->username;
        $data = $this->request->getJSON(true);
        $id_no = $data['id_no'] ?? '';
        $claim_ref = $data['claim_ref'] ?? '';
        $status = $data['status'] ?? '';
        $dttm = $data['date_time_stamp'] ?? '';
        $uid = $data['user_id'] ?? '';
        $action = $data['claim_action'] ?? '';
        if (strlen($id_no) > 0 
            && strlen($claim_ref) > 0
            && strlen($dttm) > 0
            && strlen($uid) > 0
            && strlen($action) > 0
        )
        {
            $a_act = ['id_no' => $id_no
                ,'claim_ref' => $claim_ref
                ,'status' => $status
                ,'date_time_stamp' => $dttm
                ,'userid' => $uid
                ,'claim_action' => $action
            ];

            $model = new ClaimModel();
            $act_id = $model->setClaimActivity($a_act);
            $logModel = new ApiLogModel();
            $logModel->insert([
                'username' => $username,
                'endpoint' => '/set_claim_activity',
                'ip_address' => $this->request->getIPAddress(),
                'request_data' => json_encode($data),
                'response_count' => $act_id
            ]);
            return $this->respond([
                'status' => 'success',
                'message' => '',
                'a_data' => ['activity_id' => $act_id]
            ]);                      
        }
        else 
        {
            $logModel = new ApiLogModel();
            $logModel->insert([
                'username' => $username,
                'endpoint' => '/set_claim_activity',
                'ip_address' => $this->request->getIPAddress(),
                'request_data' => json_encode($data),
                'response_count' => 0
            ]);

            return $this->respond([
                'status' => 'error',
                'message' => 'Missing required field',
                'a_data' => []
            ]);
        }
    }

    public function check_eligibility()
    {
        $username = $this->jwtUser->username;
        $data = $this->request->getJSON(true);
        $id_no = $data['id_no'] ?? '';
        $ftcode = $data['fund'] ?? '';
        $claim_ref = $data['claim_ref'] ?? '';
        $dtclm = $data['date_time_stamp'] ?? '';
        
        if ((strlen($id_no) > 0) 
            && (strlen($ftcode) > 0)
            && (strlen($claim_ref) > 0)
            && (strlen($dtclm) > 0)
            )
        {
            $model = new ClaimModel();
            $b_has_eligibility = $model->checkHasFinancial($ftcode, $id_no);
            $a_sman_acct = $model->getSeamanAccount($ftcode, $id_no);
            $sman_age = 0;
            if (!empty($a_sman_acct))
                $a_sman_acct[0]['age_in_years'];

            $a_fund = $model->getFundTypeDetails($ftcode);
            $a_fundage = $model->getSeamanFundAge($ftcode, $id_no, $dtclm);
            $a_reason = $model->getClaimReasons('');
            $curr_reason = '';
            $a_temp = [];
            foreach($a_sman_acct as $row)
            {
                $a_temp[$row['acct_no']] = ['fund' => $ftcode
                        ,'account_no' => $row['acct_no']
                        ,'status' => $row['acct_status']
                        ,'age_of_fund' => 0
                        ,'eligibility' => '' 
                        ];
            }
            $a_elig = [];
            $a_data = [];
            foreach($a_fundage as $row)
            {
                $a_elig = $this->get_allowed_claim_reason($a_reason, $a_fund, $sman_age,  $row['age_of_fund'], $row['total_years_inactive']);
                $a_temp[$row['acct_no']]['age_of_fund'] = $row['age_of_fund'];
                $a_temp[$row['acct_no']]['eligibility'] = $a_elig;
                $a_data[] = $a_temp[$row['acct_no']];
            }

            $logModel = new ApiLogModel();
            $logModel->insert([
                'username' => $username,
                'endpoint' => '/check_eligibility',
                'ip_address' => $this->request->getIPAddress(),
                'request_data' => json_encode($data),
                'response_count' => count($a_data)
            ]);           

             return $this->respond([
                'status' => 'success',
                'message' => '',
                'a_data' => $a_data
            ]);              
        }
        else 
        {
            $logModel = new ApiLogModel();
            $logModel->insert([
                'username' => $username,
                'endpoint' => '/check_eligibility',
                'ip_address' => $this->request->getIPAddress(),
                'request_data' => json_encode($data),
                'response_count' => 0
            ]);

            return $this->respond([
                'status' => 'error',
                'message' => 'Missing required field',
                'a_data' => []
            ]);            
        }
        
    }

    private function get_allowed_claim_reason($a_reason, $a_fund, $age, $fund_age, $inactive_yrs)
	{
		// check what are applicable reasons
		$reason_claim_code = '';
		if (($a_fund['retirement_age'] > 0) && ($a_fund['retirement_age'] <= $age ))
		{
			$reason_claim_code = 'OLD AGE';	
		}
		elseif ($fund_age >= 10)
		{
			$reason_claim_code = '10 YEARS';
		}
		elseif ($inactive_yrs >= $a_fund['inactive_yrs'])
		{
			$reason_claim_code = 'INACTIVE';
		}
		$a_temp = $a_reason;
		if ($reason_claim_code !== '')
		{
			if (($reason_claim_code == 'INACTIVE') || ($reason_claim_code == 'FINANCIAL')) {
				for ($i = 0; $i < count($a_reason); $i++)
				{
					if (($a_reason[$i]['reasoncode'] !== $reason_claim_code) &&
						($a_reason[$i]['reasoncode'] !== 'DEATH') && 
						($a_reason[$i]['reasoncode'] !== 'ILLNESS') && 
						($a_reason[$i]['reasoncode'] !== 'MIGRATE'))
						unset($a_temp[$i]);
				}
			}
			else {
				for ($i = 0; $i < count($a_reason); $i++)
				{
					if (($a_reason[$i]['reasoncode'] !== $reason_claim_code) &&
						($a_reason[$i]['reasoncode'] !== 'DEATH'))
						unset($a_temp[$i]);
				}
			}
		}
		else 
		{
			for ($i = 0; $i < count($a_reason); $i++)
			{
				if (($a_reason[$i]['reasoncode'] == 'OLD AGE') || 
					($a_reason[$i]['reasoncode'] == '10 YEARS') ||
					($a_reason[$i]['reasoncode'] == 'INACTIVE'))
					unset($a_temp[$i]);
			}
		}
		return ['main_reason' => $reason_claim_code
                ,'allowed_reasons' => $a_temp];
	} 
}