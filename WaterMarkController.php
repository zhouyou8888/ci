<?php


class WaterMarkController extends Controller2 {


    public function actionTest() {


        $str  = 'https://qncdn.wanshifu.com/4fc350c82734fc7d6b5767607e2cba5a?imageView2/0/interlace/1/ignore-error/1';
        $temp = array();


        $sql = "select description,content from news ";
        $rs  = Yii::app()->db->createCommand($sql)->queryAll();


        foreach ($rs as $k => $v) {


            if (strstr($v['content'], $str)) {

                $temp[] = $v;

            }
        }


        echo "<pre>";
        print_r($temp);
        echo "</pre>";


    }
}
