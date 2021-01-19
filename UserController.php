<?php

/*
 * @desc:用户管理
 * @author:darren
 * @date:2017-02-28
 * * */

class UserController extends Controller {

    private $user_table = 'ocs_user';
    private $ugroup_table = 'ocs_ugroup';

    public function actionIndex() {
        $skey = Yii::app()->request->getParam("skey", ""); //搜索用户名\姓名
        $department_id = Yii::app()->request->getParam("department_id", 0); //用户所属部门
        $status = Yii::app()->request->getParam("status", 0); //用户所属部门
        $criteria = new CDbCriteria();
        $criteria->order = 'add_time desc,id desc';
        if ($skey) {
            $criteria->addCondition("uname like '%$skey%' or chinese_name like '%$skey%'");
        }
        if ($department_id) {
            $criteria->addCondition("depart_id='{$department_id}'");
        }
        if ($status) {
            $criteria->addCondition("is_del='{$status}'");
        }
        $user = new OCS_User();
        $counts = $user->count($criteria);
        $pager = new CPagination($counts);
        $pager->pageSize = 15;
        $pager->applyLimit($criteria);
        $rows = $user->findAll($criteria);
        $curpage = Yii::app()->request->getParam("page", 1);

        $sql = " select * from {$this->ugroup_table}";
        $ugroups = Yii::app()->db1->createCommand($sql)->queryAll(); //获取所有组

        $group_rows = array();
        foreach ($ugroups as $val) {
            $group_rows[$val['id']] = $val['gname'];
        }

        $department = Yii::app()->params['department'];
        $rows = ArrayHelper::toArray($rows);
        foreach ($rows as $key => $v) {
            $rows[$key]['department'] = '';
            if (!empty($v['depart_id'])) {
                $rows[$key]['department'] = $department[$v['depart_id']];
            }

            $rows[$key]['ugroup'] = '';
            if($v['ugroup']){
                $temp = explode(',', $v['ugroup']);
                foreach ($temp as $value) {
                    if(isset($group_rows[$value])){
                     $rows[$key]['ugroup'] .=','.$group_rows[$value];
                    }
                }
                if (substr($rows[$key]['ugroup'], 0, 1) == ',') {
                    $rows[$key]['ugroup'] = substr($rows[$key]['ugroup'], 1);
                }

            }

        }
        $edit_limit = $user->getUserAuth('User','Edit');
        $pagination = Common::setPagination(Yii::app()->createUrl('/User/Index'),(int)$curpage,(int)$counts,$pager->pageSize,'',true, $_GET);
        $count_page = ceil($counts/$pager->pageSize) ;
        $this->renderPartial("index", array("edit_limit"=>$edit_limit,"group_rows" => $group_rows, "status" => $status, "department_id" => $department_id, "users" => $rows, 'pages' => $pager, "skey" => $skey, "curpage" => $curpage, "counts" => $counts, "department" => $department,'pagination'=>$pagination,'count_page'=>$count_page,));
    }

    /*public function actionAdd() {

        if (Yii::app()->request->isAjaxRequest) {
            $act = Yii::app()->request->getParam("act");
            if ($act == 'add') {
                $uname = Yii::app()->request->getParam("uname");
                $upwd = Yii::app()->request->getParam("upwd");
                $re_upwd = Yii::app()->request->getParam("re_upwd");
                $ugroup = Yii::app()->request->getParam("ugroup");
                $chinese_name = Yii::app()->request->getParam("chinese_name");
                $customer_num = Yii::app()->request->getParam("customer_num");
                $is_leader = Yii::app()->request->getParam("is_leader");
                $remark = Yii::app()->request->getParam("remark");

                if (!preg_match("/^\w{6,}$/", $upwd)) {//密码匹配
                    $arr = array("status" => 2, "msg" => "请输入至少6位的常用密码");
                    echo json_encode($arr);
                    Yii::app()->end();
                }

                if ($upwd != $re_upwd) {
                    $arr = array("status" => 3, "msg" => "密码不一致");
                    echo json_encode($arr);
                    Yii::app()->end();
                }

                $user = new OCS_User();
                $infos = $user->findByAttributes(array("uname" => $uname));
                if (!empty($infos)) {
                    $arr = array("status" => 4, "msg" => "用户名已经存在");
                    echo json_encode($arr);
                    Yii::app()->end();
                }
                $upwd = md5($upwd . Yii::app()->params ['extra'] ['key']);
                $user->uname = $uname;
                $user->upwd = $upwd;
                $user->ugroup = $ugroup;
                $user->chinese_name = $chinese_name;
                $user->customer_num = $customer_num;
                $user->is_leader = $is_leader;
                $user->remark = $remark;
                $user->add_time = time();
                $user->login_ip = Yii::app()->request->userHostAddress;
                $rs = $user->save();
                $arr = $rs ? array("status" => 0, "msg" => "操作成功") : array("status" => 1, "msg" => "操作失败");
                echo json_encode($arr);
                Yii::app()->end();
            }
        }
        $ugroup = new OCS_Ugroup();
        $ugroups = $ugroup->findAll();
        $this->renderPartial("add", array("ugroups" => $ugroups));
    }*/

    public function actionEdit() {//编辑操作
        $id = Yii::app()->request->getParam("id",'');
        $act = Yii::app()->request->getParam("act");
        $uname = Yii::app()->request->getParam("uname");
        $position = Yii::app()->request->getParam("position");
        $depart_id = Yii::app()->request->getParam("department_id");
        $email = Yii::app()->request->getParam("email");
        $phone = Yii::app()->request->getParam("phone");
        $ugroup = Yii::app()->request->getParam("role_num");
        $chinese_name = Yii::app()->request->getParam("chinese_name");
        $customer_num = Yii::app()->request->getParam("customer_num");
        if(substr($ugroup,-1)==','){
            $ugroup=substr($ugroup, 0, -1);
        }
        $user = new OCS_User();
        if ($id) {
            if("edit" != $act ){
                $userInfos = $user->findByPk($id);
                $ugroup = new OCS_Ugroup();
                $ugroups = $ugroup->findAll();
                $department =Yii::app()->params['department'];
                $group_name='';
                foreach ($ugroups as $k=>$v){
                    if(in_array($v['id'],explode(',',$userInfos['ugroup']))){
                        $group_name.=$v['gname'].' ';
                    }
                }
                $this->renderPartial("edit", array("userInfos" => $userInfos, "group_name" => $group_name,"department"=>$department));
                exit;
            }
            $row = $user->findByPk($id);
            $sql = " select count(*) as num from {$this->user_table} where uname='{$uname}' and uname!='{$row['uname']}'";
            $infos = Yii::app()->db1->createCommand($sql)->queryScalar();
            if (!empty($infos)) {
                $arr = array("status" => 4, "msg" => "用户名已经存在");
                echo json_encode($arr);
                Yii::app()->end();
            }

            $arr = array("uname" => $uname, "ugroup" => $ugroup, "chinese_name" => $chinese_name, "customer_num" => $customer_num,
                'email'=>$email,'depart_id'=>$depart_id,'phone'=>$phone,'position'=>$position);
            $rs = $user->updateAll($arr, "id=:id", array(":id" => $id));
            $arr = array("status" => 0, "msg" => "操作成功");

            /*添加自动分配到redis开始*/
            OCS_User::model()->setAuto();
  
            echo json_encode($arr);
            Yii::app()->end();

        }
        else{
            if (Yii::app()->request->isAjaxRequest) {

                $user = new OCS_User();
                $infos = $user->findByAttributes(array("uname" => $uname));
                if (!empty($infos)) {
                    $arr = array("status" => 4, "msg" => "用户名已经存在");
                    echo json_encode($arr);
                    Yii::app()->end();
                }
                $upwd = md5('123456' . Yii::app()->params ['extra'] ['key']);
                $user->uname = $uname;
                $user->position = $position;
                $user->email = $email;
                $user->phone = $phone;
                $user->depart_id = $depart_id;
                $user->upwd = $upwd;
                $user->ugroup = $ugroup;
                $user->chinese_name = $chinese_name;
                $user->customer_num = $customer_num;
                $user->add_time = time();
                $user->login_ip = Yii::app()->request->userHostAddress;
                $user->auto_send = 1;
                $rs = $user->save();
                
                /*添加自动分配到redis开始*/
                OCS_User::model()->setAuto();

                $arr = $rs ? array("status" => 0, "msg" => "操作成功") : array("status" => 1, "msg" => "操作失败");
                echo json_encode($arr);
                Yii::app()->end();
            }
            $ugroup = new OCS_Ugroup();
            $ugroups = $ugroup->findAll();
            $department =Yii::app()->params['department'];
            $this->renderPartial("add", array("groups" => $ugroups,"department" =>$department,'in_groups'=>array() ));
        }

    }

    public function actionDel() {//用户删除或禁用操作
        $id = Yii::app()->request->getParam("id");
        $act = Yii::app()->request->getParam("act");
        $user = new OCS_User();
       // $row = $user->findByPk($id);
        $rs = '';
        if ("disable" == $act) {//禁用操作
            $rs = $user->updateAll(array("is_del" => '2'), "id=:id", array(":id" => $id));
            $arr = $rs ? array("status" => 0, "msg" => "操作成功") : array("status" => 1, "msg" => "操作失败");
        }
        if ("able" == $act) {//启用操作
            $rs = $user->updateAll(array("is_del" => '1'), "id=:id", array(":id" => $id));
            $arr = $rs ? array("status" => 0, "msg" => "操作成功") : array("status" => 1, "msg" => "操作失败");
        }
        if ("del" == $act) {//删除操作
            $obj = new User();
            $rs = $obj->updateByPk($id,array("is_del"=>3));
            $arr = $rs ? array("status" => 0, "msg" => "操作成功") : array("status" => 1, "msg" => "操作失败");
        }
        if ("reset" == $act) {//重置操作
            $upwd = md5('123456' . Yii::app()->params ['extra'] ['key']);
            $rsset = $user->updateAll(array("upwd" => $upwd), "id=:id", array(":id" => $id));
            $arr = $rsset ? array("status" => 0, "msg" => "操作成功") : array("status" => 1, "msg" => "操作失败");
        }
        if ($rs) {
            /*添加自动分配到redis开始*/
            OCS_User::model()->setAuto();
        }
        echo json_encode($arr);
        Yii::app()->end();
    }

    public function actionEditrole(){
        $role_num = Yii::app()->request->getParam("role_num",array());
        if(!empty($role_num)){
            if(substr($role_num,-1)==','){
              $role_num=substr($role_num, 0, -1);
            }
           $role_num= explode(',',$role_num);
        }
        $sql = " select * from {$this->ugroup_table}";
        $ugroups = Yii::app()->db1->createCommand($sql)->queryAll(); //获取所有组
        $group_rows = array();
        foreach ($ugroups as $val) {
            $group_rows[$val['id']] = $val['gname'];
        }
       // var_dump($this->ugroup);exit;
       /* $ugroup_array=$this->ugroup;
        if($ugroup_array[0]!='99999'){
            $users = new OCS_User();
            $user_info= $users->getUserInfosByIds($this->uid);
            foreach ($group_rows as $k=>$v){
                if(in_array($k,explode(',',$user_info['ugroup']))){
                    unset($group_rows[$k]);
                }
            }
        }*/
        $this->renderPartial("editrole", array("groups" => $group_rows,"in_groups"=>$role_num));
    }
}
