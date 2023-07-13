<?php
namespace app\myapp\controller;

use think\Controller;
use think\Db;
use think\Ecryption;

use app\myapp\model\EhrEmployee as EE;
use app\myapp\model\GSqlStores as GSS;

class Sqlstore extends Controller
{
    /**
     * @return void
     * @TODO 用于接收登录
     */
    public function login()
    {
        $UserID=request()->param("userid");
        if($UserID==""){
            echo '<script>function a(){alert("您的工号获取有误！");return false;} a();</script>';
            exit();
        }
        $UserID=Ecryption::decrypt($UserID);
        $modelEE=new EE();
        $resultEE=$modelEE->where(['[人员编号]'=>['eq',$UserID]])->field('[人员编号] as UserID,[姓名] as UserName')->select();
        if(count($resultEE)<=0){
            echo '<script>function a(){alert("您的工号不在EHR中！");return false;} a();</script>';
            exit();
        }
        session("UserID",[$resultEE[0]->UserID,$resultEE[0]->UserName]);
        header('location:index');
        exit();
    }
    public function index()
    {
        if(!session("UserID")){
            $UserID=request()->param("UserID");
            $UserID=Ecryption::decrypt($UserID);
            $modelEE=new EE();
            $resultEE=$modelEE->where(['[人员编号]'=>['eq',$UserID]])->field('[人员编号] as UserID,[姓名] as UserName')->select();
            session("UserID",[$resultEE[0]->UserID,$resultEE[0]->UserName]);
            if(!session("UserID")){
                echo '<script>function a(){alert("您的工号获取有误！");return false;} a();</script>';
                exit();
            }
        }
        $UserInfo=session("UserID");
        $modelGSS=new GSS();
        $resultFold=$modelGSS->where(['Levels'=>['eq',1],'Types'=>['eq','fold'],'OwnerID'=>['eq',$UserInfo[0]],'DelFlag'=>['eq',0]])->order("ID")->select();
        $resultSql=$modelGSS->where(['Levels'=>['eq',1],'Types'=>['eq','sql'],'OwnerID'=>['eq',$UserInfo[0]],'DelFlag'=>['eq',0]])->order("ID")->select();
        for($i=0;$i<count($resultFold);$i++){
            $resultFold[$i]['ID']=Ecryption::ecrypt($resultFold[$i]['ID']);
        }
        for($i=0;$i<count($resultSql);$i++){
            $resultSql[$i]['ID']=Ecryption::ecrypt($resultSql[$i]['ID']);
        }
        $this->assign("UserID",Ecryption::ecrypt($UserInfo[0]));
        $this->assign("UserName",Ecryption::ecrypt($UserInfo[1]));
        $this->assign("folds",$resultFold);
        $this->assign("sqls",$resultSql);
        return view();
    }


    /**
     * @return void
     * 用于对目录的进行一次跳转
     */
    public function jump()
    {
        $info=request()->param();
        $ID=$info['ID'];
        $UserID=$info['UserID'];
        $UserName=$info['UserName'];
       /* dump($info);
        dump($ID.$UserID.$UserName);
        die();*/
       /* $ID=Ecryption::ecrypt($ID);
        $UserID=Ecryption::ecrypt($UserID);
        $UserName=Ecryption::ecrypt($UserName);*/
        header("Location:showDir?ID=$ID&UserID=$UserID&UserName=$UserName");
        exit();
    }

    public function showDir()
    {
        $info=request()->param();
        if(!session("UserID")){
            $UserID=$info['UserID'];
            $UserName=$info['UserName'];
            $UserID=Ecryption::decrypt($UserID);
            $UserName=Ecryption::decrypt($UserName);
            session("UserID",[$UserID,$UserName]);
        }
        $ID=$info['ID'];
        $ID=Ecryption::decrypt($ID);
        $UserInfo=session("UserID");
        $modelGSS=new GSS();
        $resultFold=$modelGSS->where(['Types'=>['eq','fold'],'OwnerID'=>['eq',$UserInfo[0]],'DelFlag'=>['eq',0],'ParentID'=>['eq',$ID]])->order("ID")->select();
        $resultSql=$modelGSS->where(['Types'=>['eq','sql'],'OwnerID'=>['eq',$UserInfo[0]],'DelFlag'=>['eq',0],'ParentID'=>['eq',$ID]])->order("ID")->select();

        for($i=0;$i<count($resultFold);$i++){
            $resultFold[$i]['ID']=Ecryption::ecrypt($resultFold[$i]['ID']);
        }
        for($i=0;$i<count($resultSql);$i++){
            $resultSql[$i]['ID']=Ecryption::ecrypt($resultSql[$i]['ID']);
        }

        $resultFoldCur=$modelGSS->get(['ID'=>['eq',$ID]]);
        $Levels=$resultFoldCur['Levels']+1;
        $dir=[];
        $index=0;
        $dir[$index]=[$resultFoldCur['ShortDescr'],Ecryption::ecrypt($ID)];
        while($resultFoldCur->ParentID!=NULL&&$index<100){
            $index++;
            $resultFoldCur=$modelGSS->get(['ID'=>['eq',$resultFoldCur['ParentID']]]);
            $dir[$index]=[$resultFoldCur['ShortDescr'],Ecryption::ecrypt($resultFoldCur['ID'])];
        }
        $index++;
        $dir[$index]=['根目录',0];
        $dirNew=[];
        for($i=count($dir)-1;$i>=0;$i--){
            $dirNew[count($dir)-1-$i]=$dir[$i];
        }
        $this->assign("UserID",Ecryption::ecrypt($UserInfo[0]));
        $this->assign("UserName",Ecryption::ecrypt($UserInfo[1]));
        $this->assign("folds",$resultFold);
        $this->assign("sqls",$resultSql);
        $this->assign("dir",$dirNew);
        $this->assign("ID",Ecryption::ecrypt($ID));
        $this->assign("Levels",$Levels);
        return view();
    }

    public function showOneSql()
    {
        $ID=request()->param("ID");
        $ID=Ecryption::decrypt($ID);
        $modelGSS=new GSS();
        $resultGSS=$modelGSS->get(['ID'=>['eq',$ID]]);
        $this->assign("info",$resultGSS);
        return view();
    }

    /**
     * @return array
     * @TODO 根据ID设置ShortDescr
     */
    public function setShortnameByID()
    {
        Db::startTrans();
        try {
            $id = request()->param("id");
            $str = request()->param("str");
            $id=Ecryption::decrypt($id);
            $modelGSS = new GSS();
            $modelGSS->isUpdate(true)->save(['ShortDescr' => $str], ['ID' => ['eq', $id]]);
            Db::commit();
            return ['errcode'=>0,'errmsg'=>'ok'];
        }catch (\Exception $e){
            Db::rollback();
            return ['errcode'=>1,'errmsg'=>$e->getMessage()];
        }
    }

    /**
     * @return array
     * @TODO 根据ID删除
     */
    public function deleteByID()
    {
        Db::startTrans();
        try{
            $id = request()->param("id");
            $id=Ecryption::decrypt($id);
            $modelGSS = new GSS();
            //$modelGSS->where(['ID' => ['eq', $id]])->delete();
            $modelGSS->isUpdate(true)->save(['DelFlag'=>1],['ID' => ['eq', $id]]);
            $this->recursion($id);
            Db::commit();
            return ['errcode'=>0,'errmsg'=>'ok'];
        }catch (\Exception $e){
            Db::rollback();
            return ['errcode'=>1,'errmsg'=>$e->getMessage()];
        }
    }

    public function test()
    {
        $id = request()->param("id");
        $modelGSS = new GSS();

        //$modelGSS->where(['ID' => ['eq', $id]])->delete();
        //$modelGSS->isUpdate(true)->save(['DelFlag'=>1],['ID' => ['eq', $id]]);
        dump($id);
        $this->recursion($id);
    }

    /**用于递归删除***/
    private function recursion($ID)
    {
        $modelGSS=new GSS();
        $resultGSS=$modelGSS->all(['ParentID'=>['eq',$ID]]);
        if(count($resultGSS)<=0) {
            return 1;
        }
        foreach ($resultGSS as $k=>$v){
            $modelGSS=new GSS();
            $modelGSS->isUpdate(true)->save(['DelFlag'=>1],['ID' => ['eq',$v['ID']]]);
            $v['Types']=='fold'?$this->recursion($v['ID']):"";
        }
    }



    /**
     * @return array
     * @TODO 确保是SQL类型文件，根据ID返回结果
     */
    public function getSqlInfoByID()
    {
        try {
            $id = request()->param("id");
            $id=Ecryption::decrypt($id);
            $modelGSS = new GSS();
            $resultGSS = $modelGSS->get(['ID' => ['eq', $id]]);
            $resultGSS['ID']=Ecryption::ecrypt($resultGSS['ID']);
            return ['errcode'=>0,'errmsg'=>$resultGSS];
        }catch(\Exception $e){
            return ['errcode'=>1,'errmsg'=>$e->getMessage()];
        }
    }

    /**
     * @return array
     * @TODO 根据ID或者无ID，修改或添加一条内容
     */
    public function addOrChangeOne()
    {
        Db::startTrans();
        try {
            $info = request()->param();
            $map = [];
            foreach ($info as $k => $v) {
                if ($k != 'ID') {
                    if($k=='ParentID'||$k=='OwnerID'||$k=='OwnerName'){
                        $map[$k]=Ecryption::decrypt($v);
                    }else{
                        $map[$k] = $v;
                    }
                }
            }
            $modelGSS = new GSS();
            if ($info['ID'] == "") {
                $map['CreateTime'] = date("Y-m-d H:i:s");
                $modelGSS->isUpdate(false)->save($map);
            } else {
                $modelGSS->isUpdate(true)->save($map, ['ID' => ['eq', Ecryption::decrypt($info['ID'])]]);
            }
            $resultGSS=$modelGSS->where(['OwnerID'=>['eq',$map['OwnerID']],'Types'=>['eq',$map['Types']]])->max("ID");
            Db::commit();
            return ['errcode'=>0,'errmsg'=>Ecryption::ecrypt($resultGSS)];
        }catch (\Exception $e){
            Db::rollback();
            return ['errcode'=>1,'errmsg'=>$e->getMessage(),'errsql'=>$map];
        }
    }
}