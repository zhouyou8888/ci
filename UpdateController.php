<?php

/*
 * @desc:系统更新日志
 * @author:xpj
 * @date:2017-12-19
 * * */

class UpdateController extends Controller {

    public function actionIndex() {
        $criteria = new CDbCriteria();
        $criteria->order = 'add_time desc,id desc';
        $model   = new OcsUpdateLog();
        $counts = $model->count($criteria);
        $pager  = new CPagination($counts);
        $pager->pageSize = 15;
        $pager->applyLimit($criteria);
        $rows = $model->findAll($criteria);
        $curpage = Yii::app()->request->getParam("page",1);
        $this->renderPartial("index",array("rows"=>$rows,'pages'=>$pager,"curpage"=>$curpage,"counts"=>$counts));
    }

    /**
     * 添加日志
     * @throws CException
     */
    public function actionAdd(){
        $act = Yii::app()->request->getParam("act");
        if( $act == "add"){
            $user = Yii::app()->session[Yii::app()->params['sessionPre']."user_infos"];
            $model = new OcsUpdateLog();
            $model->version   = Yii::app()->request->getParam("version");
            $model->username   = $user['uname'];
            $model->content   = Yii::app()->request->getParam("content");
            $model->remark   = Yii::app()->request->getParam("remark");
            $model->add_time  = time();
            $rs = $model->save();
            $arr = $rs ? array("status"=>0,"msg"=>"添加日志成功") : array("status"=>1,"msg"=>"添加日志失败");
            echo json_encode($arr);
            Yii::app()->end();
        }
        $nav = Yii::app()->params['nav'];
        $this->renderPartial("add",array("nav"=>$nav) );
    }

    /**
     *
     * @throws CException
     */
    public function actionEdit(){
        $id = Yii::app()->request->getParam("id");
        $act = Yii::app()->request->getParam("act");

        if( "edit" == $act){
            $user = Yii::app()->session[Yii::app()->params['sessionPre']."user_infos"];
            $model = (new OcsUpdateLog())->findByPk($id);
            $model->version   = Yii::app()->request->getParam("version");
            $model->username   = $user['uname'];
            $model->content   = Yii::app()->request->getParam("content");
            $model->remark   = Yii::app()->request->getParam("remark");
            //$model->add_time  = time();
            $rs = $model->save();
            $arr = $rs ? array("status"=>0,"msg"=>"编辑日志成功") : array("status"=>1,"msg"=>"编辑日志失败");
            echo json_encode($arr);
            Yii::app()->end();
        }
        $model = new OcsUpdateLog();
        $info = $model->findByPk($id);
        if(!$info){
            exit('');
        }
        $nav = Yii::app()->params['nav'];
        $this->renderPartial("edit",array("info"=>$info,"nav"=>$nav) );
    }
}
