<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use wechat\wxBizDataCrypt;
use think\facade\Env;

class Checkin extends Controller{

  public function initialize(){
        
    }
   public function index(){
    return;
   }

   //用户发布自我打卡记录
   public function selfCheckin(){
    $openid=input('openId');
    $type=input('type');
     
    $nickname=db('users')->field('username')->where('openid',$openid)->find();
    if(!$nickname){
    	return json(['status'=>0,'msg'=>'缺少用户信息']);
    }else{
	    $publish=$nickname['username'];

	    $scoreType=array('超慢跑打卡记录','超慢跑30分钟打卡记录','超慢跑30-60分钟打卡记录','超慢跑60分钟以上打卡记录','自我训练每日打卡记录','阅读每日打卡','发表感悟','点赞','评论','意见反馈','分享文章');
	    $catidNum=array(5,5,5,5,4,6,6,'','',7,'');
	    $catid=$catidNum[$type];
	    $createtime=db('picture')->field('createtime')->where(array('publish'=>$openid,'catid'=>$catid))->order('id', 'desc')->find();
	    $createtime=date("Y-m-d",$createtime['createtime']);
	    //return ($createtime);
	    if($createtime==date("Y-m-d",time()) && $type!=9){
	    	return json(['status'=>1,'msg'=>'今天已经打过卡，请勿重复操作','info'=>[]]);
	    }	    
	    $title=$publish."-".$scoreType[$type]."-".date("Y-m-d",time());
	    
	    // if($type==4){
	    // 	$title=$publish."-自我打卡记录-".date("Y-m-d",time());
	    // 	$catid=4;
	    // }elseif($type==0){
	    // 	$title=$publish."-超慢跑打卡记录-".date("Y-m-d",time());
	    // 	$catid=5;
	    // }elseif($type==9){
	    // 	$title=$publish."-意见反馈-".date("Y-m-d",time());
	    // 	$catid=7;
	    // }  
	    
	    $data=[
	    	'catid'    		=>$catid,
	    	'userid'   		=>2,
	    	'username' 		=>'admin',
	    	'title'    		=>$title,
	    	'content'  		=>input('content'),
	    	'createtime'    =>time(),
	    	'updatetime'	=>time(),
	    	'pic'			=>input('imgUrl'),
	    	'group'			=>1,
	    	'publish'		=>$openid
	    ];
	    db('picture')->insert($data);
	    //$result['info']=$data;
	    $model = new \app\api\controller\Index;
	    $aid='';

	    $result=$model->updateScore($openid,$type,$aid);
	    // $result['msg']="今天打卡成功";
	    // $result['msg']="发布意见反馈成功";
	    $result['msg']="发布".$scoreType[$type]."成功";
    }
 
    // $model = new \app\api\controller\Index;
    // $result=$model->updateScore($openid,$type,$aid);
    //$result=$this->updateScore($openid,$type,$aid);
    //$result = callback('index:updateScore', array($openid,$type,$aid));
    //$result = $this->action('index:updateScore', array('openid'=>$openid,'type'=>$type,'aid'=>''));
    return json($result);
   }

   //自我打卡记录显示
   public function checkinList(){
   	    $openid=input('openId');
   	    $type=input('type');
   	    if($type==6){
   	    	$catid=6;
   	    }
   	    elseif($type==4){
   	    	$catid=4;
   	    }elseif($type==0){
   	    	$catid=5;
   	    }else{
   	    	$catid=input('catid');
   	    }
   	    
        $page=input('page');
        if(!$page){
        	$page=1;
        }
        $list=db('picture')->field('title,content,createtime,pic')->where(array('publish'=>$openid,'catid'=>$catid))->order('createtime','desc')->page($page)->limit(30)->select();
        $ckeckScore=db('user_score')->where(array('openid'=>$openid,'type'=>$type))->sum('score');
        $ckeckCount=db('user_score')->where(array('openid'=>$openid,'type'=>$type))->count();
        if(!$list){
                 $result =['status'=>1,'msg'=>'没有记录'];
            }else{
                $result=['status'=>1,'msg'=>'读取打卡记录成功'];
                foreach ($list as $k=>$v){
                    
                    $result['info'][$k]['createtime']=date("Y-m-d",$v['createtime']);
                    $result['info'][$k]['content']=$v['content'];                    
                    $result['info'][$k]['title']=$v['title'];
                    $result['info'][$k]['pic']=$v['pic'];
                }
                $result['score']=$ckeckScore;
                $result['count']=$ckeckCount;
            }
        return json($result);
   }
   



}