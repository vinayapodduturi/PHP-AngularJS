<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdduserController
 *
 * @author vinaya
 */
class AdduserController extends Controller {

    public function actionGetPassword() {

        $password = Yii::app()->epassgen->generate(10, 2, 2, 1);

        $JSON = CJSON::encode(array('password' => $password));
        echo $JSON;
    }

    public function actionVerifyEmail() {
        try {

            $_POST = json_decode(file_get_contents('php://input'), true);
            $email = $_POST['Email'];
            $response = Authentication::model()->checkUserNameExist($email);
            if (isset($response) && count($response) > 0) {
                $data['error'] = 'Email already exists.';
            } else {
                $data['success'] = 'Email does not exists.';
            }
            echo json_encode($data);
        } catch (Exception $exc) {
            error_log("Exception from Controller_" . $exc->getMessage());
        }
    }

    public function actionGetAllEmailAddress() {
        try {
            $data = AddUser::model()->getEmailAddress();
            if (isset($data)) {
                echo $JSON = CJSON::encode(array(
                    'status' => 'Success',
                    'statusDescription' => 'message',
                    'code' => 200,
                    'data' => $data
                ));
            } else {
                echo $JSON = CJSON::encode(array(
                    'status' => 'Fail',
                    'statusDescription' => 'No data found',
                    'code' => 404,
                    'data' => $data
                ));
            }
        } catch (Exception $exc) {
            error_log("Exception from Controller_" . $exc->getMessage());
        }
    }

    public function actionSubmitFormData() {

        $data = array();
        $model = new AddUser();
        try {
            $request = json_decode(file_get_contents("php://input"), true);

            if (isset($request)) {
                $formdata = $request['formdata'];
                $email = $request['Email'];

                $Role_id = $request['Role_id'];
                $Role_Type = $request['Role_Type'];

                if (isset($request['Account_id'])) {
                    $Account_id = $request['Account_id'];
                } else {
                    $Account_id = "";
                }
                if (isset($request['PMRole_id'])) {
                    $PMRole_id = $request['PMRole_id'];
                } else {
                    $PMRole_id = "";
                }
                if (isset($request['PMAccount_id'])) {
                    $PMAccount_id = $request['PMAccount_id'];
                } else {
                    $PMAccount_id = "";
                }
                if (isset($request['Account_Type'])) {
                    $Account_Type = $request['Account_Type'];
                } else {
                    $Account_Type = "";
                }
                $model->ActivationKey = sha1(mt_rand(10000, 99999) . time() . $email);
                $ActivationKey = $model->ActivationKey;
                $model->Status = 0;
                $Status = $model->Status;
                //if(empty($errors)){
                $result = AddUser::model()->saveUserData($formdata, $email, $Role_Type, $Account_Type, $ActivationKey, $Status);
                $user_record['id'] = $result;
                $user_record['ActivationKey'] = $ActivationKey;
                $user_record['Status'] = $Status;
                $id = $result;
                $record = AddUserCollection::model()->saveUserRecord($user_record, $formdata, $email, $Role_Type, $Account_Type);
                $data['message'] = 'User added successfully.';
                echo json_encode($data);
                $roleid_result = UserRole::model()->saveUserRole($request['id_Role'], $id);
                $accountid_result = UserAccount::model()->saveUserAccount($Account_id, $id, $Role_id);
                $pmaccountid_result = UserAccount::model()->savePMAccount($PMAccount_id, $id, $PMRole_id);
                if (isset($result)) {
                    $reglink = array("reglink" => Yii::app()->createAbsoluteUrl('adduser/activate', array('Email' => $email, 'Password' => $formdata['Password'], 'ActivationKey' => $ActivationKey)));
                    Yii::import('ext.yii-mail.YiiMailMessage');
                    Yii::app()->mail->transportOptions = array(
                        // 'host' => 'aspmx.l.google.com',
                        'host' => 'smtp.gmail.com',
                        'username' => 'exampletest28@gmail.com',
                        'password' => "Activationlink@",
                        'port' => '465',
                        'encryption' => 'ssl',
                    );
                    Yii::app()->mail->transportType = "smtp"; //Uncomment these when email is configured in admin section for Template management
                    $message = new YiiMailMessage;
                    $message->view = 'registration';
                    $message->setBody($reglink,'text/html');
                    $message->subject = 'Activation link';
                    $message->addTo($email);
                    $message->from = 'exampletest28@gmail.com';
                    if (Yii::app()->mail->send($message)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    $data['errors'] = "Something went wrong.";
                    //echo 'NUF';
                }
                echo json_encode($data);
            }
        } catch (Exception $exc) {
            error_log("error in Controller" . $exc->getMessage());
        }
    }

    public function actionActivate() {
        $email = Yii::app()->request->getQuery('Email');
        $ActivationKey = Yii::app()->request->getQuery('ActivationKey');
        $result = AddUser::model()->activateUserByEmail($email, $ActivationKey);
        if ($result) {
            $this->redirect('/#/resetpassword?Email=' . $email);
        } else {
            $message = "Your account has been activated.";
            $this->redirect('/#/login?message=' . $message);
        }
    }

}
