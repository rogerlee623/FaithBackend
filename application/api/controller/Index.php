<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Cache;
use think\Request;
use wechat\wxBizDataCrypt;
use think\facade\Env;

class Index extends Controller{

    protected $serverUrl,$session_key;
    //protected $httpurl=$_SERVER['HTTP_HOST'];
    public function initialize(){
        parent::initialize();
        $currentHost = request()->host();
        $currentProtocol = request()->scheme();
        $this->serverUrl=$currentProtocol."://".$currentHost;
    }
    //栏目信息调用
    public function index(){
        $openid=input('openId');
        if(!input('catId')){
            $result =['code'=>0,'msg'=>'缺少栏目参数'];
        }else{
            $info_page=input('page');
            if(!$info_page){
              $info_page=1;  
            }

            $info=db('article')->field('id,title,description,createtime,tags,thumb')->where('catid',input('catId'))->order('createtime','desc')->page($info_page)->limit(10)->select();

            if(!$info){
                 $result =['status'=>1,'msg'=>'此栏目没有信息','info'=>[]];
            }else{
                $result=['status'=>1,'msg'=>'读取第'.$info_page.'页信息'];
                foreach ($info as $k=>$v){
                    $result['info'][$k]['id']=$v['id'];
                    if($openid){
                        $like=db('user_score')->where(array('aid'=>$v['id'],'openid'=>$openid))->find();
                    }
                    if(!$like){
                        $result['info'][$k]['like']=0;
                    }else{
                        $result['info'][$k]['like']=1;
                    }
                    $result['info'][$k]['type']=$v['tags'];                
                    $result['info'][$k]['src']=$this->serverUrl.$v['thumb'];
                    $result['info'][$k]['title']=$v['title'];
                    $result['info'][$k]['time']=date("Y-m-d",$v['createtime']);
                    $result['info'][$k]['description']=$v['description'];

                }
                
                
            }

        }
        return json($result);

    }
    //首页Banner
    public function banner(){
       //Env::get('root_path')
        $adList = Db::name('ad')->where(array('as_id'=>1,'open'=>1))->order('sort asc')->select();
        if(!$adList){
            $result =['status'=>1,'msg'=>'没有相关信息','info'=>[]];
        }else{
            $result =['status'=>1,'msg'=>'读取成功'];
            foreach ($adList as $k=>$v){
            $result['info'][$k]['id']=$v['id'];  
            $result['info'][$k]['src']=$this->serverUrl.$currentHost.$v['pic'];
            $result['info'][$k]['title']=$v['title'];
            $result['info'][$k]['time']=date("Y-m-d",$v['addtime']);
            $result['info'][$k]['url']=$v['url'];
            //$result['info'][$k]['description']=$v['content'];
            }   
        } 
        return json($result);
    }

    //首页推荐信息调用
    // public function homeInfo(){
    //     $page=input('page');
    //         if(!$page||$page==''){
    //           $page=1;  
    //         }
    //     $info=db('article')->field('id,title,description,createtime,tags,thumb')->where('posid','1')->order('createtime','desc')->page($page)->limit(10)->select();
    //     if(!$info){
    //              $result =['status'=>0,'msg'=>'没有推荐信息'];
    //         }else{
    //             $result=['status'=>1,'msg'=>'读取第'.$page.'页信息'];
    //             foreach ($info as $k=>$v){
    //                 $result['info'][$k]['id']=$v['id'];
    //                 $result['info'][$k]['type']=$v['tags'];                
    //                 $result['info'][$k]['src']=$this->serverUrl.$v['thumb'];
    //                 $result['info'][$k]['title']=$v['title'];
    //                 $result['info'][$k]['time']=date("Y-m-d",$v['createtime']);
    //                 $result['info'][$k]['description']=$v['description'];
    //             }   
    //         }
    //     return json($result);
    // }
     public function homeInfo(){
            $page=input('page');
                if(!$page||$page==''){
                  $page=1;  
                }
             //$adList = Db::name('ad')->where(array('as_id'=>1,'open'=>1))->order('sort asc')->select();
            $info=db('ad')->where(array('as_id'=>5,'open'=>1))->order('sort','asc')->page($page)->limit(10)->select();
            if(!$info){
                     $result =['status'=>1,'msg'=>'没有推荐信息','info'=>[]];
                }else{
                    $result=['status'=>1,'msg'=>'读取第'.$page.'页信息'];
                    foreach ($info as $k=>$v){
                        $result['info'][$k]['id']=$v['url'];
                        $result['info'][$k]['type']='';                
                        $result['info'][$k]['src']=$this->serverUrl.$v['pic'];
                        $result['info'][$k]['title']=$v['content'];
                        $result['info'][$k]['time']=date("Y-m-d",$v['addtime']);
                        $result['info'][$k]['description']='';
                    }   
                }
            return json($result);
        }



    //读取文章信息
    public function article(){
        $id=input('id');
        if(!$id){
         $result =['status'=>0,'msg'=>'缺少必要参数'];   
        }else{
          $info=db('article')->field('id,title,description,createtime,tags,thumb,content,videolink,videoup')->where('id',$id)->find();
          if($info){
          $result=['status'=>1,'msg'=>'读取文章id:'.$id."成功"];          
          $result['info']['id']=$info['id'];
          $result['info']['type']=$info['tags'];                
          $result['info']['src']=$this->serverUrl.$info['thumb'];
          $result['info']['title']=$info['title'];
          $result['info']['time']=date("Y-m-d",$info['createtime']);
          $result['info']['videolink']=$info['videoup'];  
          $result['info']['content']=$info['content'];    
          }else{
             $result =['status'=>1,'msg'=>'文章不存在','info'=>[]];
          }
               
        }
        return json($result);
     }




   public function login()
    {
        $APPID = 'wxacc29fa6cd594879';
        $AppSecret = 'b3e0ec0df2f60366788c253199b6d7a4';
        $UserOpenid=cache('UserCache');
        $UserOpenid=$UserOpenid['openid'];
        $sign=input('sign');
        if(!$sign){
            $sign="67896789";
        }
        if(!$UserOpenid){

         //开发者使用登陆凭证 code 获取 session_key 和 openid        
        $code = input('code');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $arr = $this->vget($url); // 一个使用curl实现的get方法请求
        $arr = json_decode($arr, true);
        $openid = $arr['openid'];
        $session_key = $arr['session_key'];

        $signature = input('signature');
        $rawData = $_GET['rawData'];
        $encryptedData = input('encryptedData');
        $iv = input('iv');
        //return json($sign);

         $DataOpenid=db('users')->field('id,sex,reg_time,last_login,mobile,openid,unionid,username,avatar,level,score,token,sign')->where('openid',$openid)->find();
        if(!$DataOpenid){
             $result=$this->getSignature($session_key,$signature,$rawData,$encryptedData,$iv,$APPID,$sign);
             
        }else{
            $result=$this->logininfo($openid);
        }
         
      }else{
         $result=$this->logininfo($UserOpenid);
      }
      // 记录缓存 7天
      $arr['sign']= $sign;
      //cache('UserCache', $arr, 86400 * 7);
      return json($result);
  
}


    public function logininfo($openid){
        $DataOpenid=db('users')->field('id,sex,reg_time,last_login,mobile,openid,unionid,username,avatar,level,score,token,sign')->where('openid',$openid)->find();
        
        if(db('users')->where('id='.$DataOpenid['id'])->update(['last_login'=>time()])!==false){
                $result['status']=1;
                $result['msg']='登录成功';

                $DataOpenid['reg_time']=date("Y-m-d",$DataOpenid['reg_time']);
                $DataOpenid['last_login']=date("Y-m-d",$DataOpenid['last_login']);
                $levelname=db('user_level')->field('level_name')->where('level_id',$DataOpenid['level'])->find();
                $DataOpenid['level']=$levelname['level_name'];

                $result['info']=$DataOpenid;
                return $result;
                }else{
                    return ['status'=>0,'msg'=>'未知错误，登录失败!'];
                }              

    }


    public function getSignature($session_key,$signature,$rawData,$encryptedData,$iv,$APPID,$sign){   
      
        $signature2 = sha1($rawData . $session_key);
        //{"session_key":"sJJ9GX6qkkEY9qJzWEnuTQ==","openid":"ohiEf7fXD2LLbjhJFo0cUGsLOXA0"}
        if ($signature != $signature2) {
           return ['status' => 0, 'msg' => '数据签名验证失败！'];
        }
        //Vendor("wechat.wxBizDataCrypt"); //加载解密文件，在官方有下载

        $pc = new wxBizDataCrypt($APPID, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data); 
        //其中$data包含用户的所有数据       
        // [{"openId":"ohiEf7fXD2LLbjhJFo0cUGsLOXA0","nickName":"微信用户","gender":0,"language":"","city":"","province":"","country":"","avatarUrl":"https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132","watermark":{"timestamp":1718744669,"appid":"wxacc29fa6cd594879"}}]
        if ($errCode == 0) {
            $result=['status'=>1,'msg'=>'数据签名验证成功,解密成功'];
            //$result['info']='['.$data.']';            
            $data=json_decode($data,true);

            $userData['openid']=$data['openId'];            
            $userData['username']=$data['nickName'];
            $userData['sex']=$data['gender'];
            $userData['avatar']=$data['avatarUrl'];
            $userData['reg_time']=time();
            $userData['last_login']=time();
            $userData['password']=$this->token($openid);
            $userData['token']=$this->token($openid);
            $userData['mobile']='';
            $userData['unionid']='';
            $userData['level']=1;
            $userData['sign']=$sign;
            //$userData['score']=5;
            db('users')->insert($userData);

            $userData['reg_time']=date("Y-m-d",$userData['reg_time']);
            $userData['last_login']=date("Y-m-d",$userData['last_login']);            
            $levelname=db('user_level')->field('level_name')->where('level_id',1)->find();
            $userData['level']=$levelname['level_name'];
            unset($userData['password']);
            $result['info']=$userData;
            

        } else {
            $result=['status'=>1,'msg'=>'数据签名验证成功,解密失败'];
            $result['info']=$errCode;
            
        }
        return $result;
    }




//点赞
    public function like(){
        $aid=input('aid');
        $openid=input('openId');
        $type=7;

        if(!$aid){
            return json(['status'=>0,'msg'=>'缺少文章参数']);
        }else{
            $article=db('user_score')->where(array('openid'=>$openid,'aid'=>$aid,'type'=>$type))->find();
            if(!$article){
                $result=$this->updateScore($openid,$type,$aid);
                $result=json_encode($result,true);
                $result=json_decode($result,true);
                if($result['status']==1){
                    $result['msg']='点赞成功，积分增加成功';
                }
                return json($result);
            }else{
                return json(['status'=>0,'msg'=>'请勿重复操作']);
            }
        }
    }
//点赞删除
    public function dellike(){
        $aid=input('aid');
        $openid=input('openId');
        $type=7;

        if(!$aid){
            return json(['status'=>0,'msg'=>'缺少文章参数']);
        }else{
            $article=db('user_score')->where(array('openid'=>$openid,'aid'=>$aid,'type'=>$type))->find();
            if(!$article){
                $result=$this->updateScore($openid,$type,$aid);
                $result=json_encode($result,true);
                $result=json_decode($result,true);
                if($result['status']==1){
                    $result['msg']='点赞成功，积分增加成功';
                }
                return json($result);
            }else{
                return json(['status'=>0,'msg'=>'请勿重复操作']);
            }
        }
    }  

    //分享  
    public function share(){
        $aid=input('aid');
        $openid=input('openId');
        $type=10;

        if(!$aid){
            return json(['status'=>0,'msg'=>'缺少文章参数']);
        }else{
            $article=db('user_score')->where(array('openid'=>$openid,'aid'=>$aid,'type'=>$type))->find();
            if(!$article){
                $result=$this->updateScore($openid,$type,$aid);
                $result=json_encode($result,true);
                $result=json_decode($result,true);
                if($result['status']==1){
                    $result['msg']='分享成功，积分增加成功';
                }
                return json($result);
            }else{
                return json(['status'=>0,'msg'=>'请勿重复操作']);
            }
        }
    } 
//获取用户信息
    public function getMyInfo(){
        $openid=input('openId');
        if($openid){
            $result=$this->logininfo($openid);
        }else{
            $result=['status'=>0,'msg'=>'缺少关键参数'];
        }
        return json($result);
    }



    public function messagelist(){
        $aid=input('aid');
        $page=input('page');
        $openid=input('openId');
        if(!$page||$page==''){
                  $page=1;  
        }

        if(!$openid){
            $list=db('message')->field('addtime,content,name,openid,aid')->where('aid',$aid)->order('addtime','desc')->page($page)->limit(100)->select();
        }else{
            $list=db('message')->field('addtime,content,name,openid,aid')->where('openid',$openid)->order('addtime','desc')->page($page)->limit(100)->select();
        }
        
        if(!$list){
                 $result =['status'=>1,'msg'=>'没有留言','info'=>[]];
            }else{
                $result=['status'=>1,'msg'=>'读取留言成功'];
                foreach ($list as $k=>$v){
                    if(!$openid){
                      $userinfo=db('users')->field('avatar,username')->where('openid',$v['openid'])->find();
                       $result['info'][$k]['nickname']=$userinfo['username'];
                       $result['info'][$k]['avatarurl']=$userinfo['avatar'];
                         
                         $result['info'][$k]['article']='';
                    } else{
                       $result['info'][$k]['nickname']=$v['name'];
                       $articleInfo=db('article')->field('title')->where('id',$v['aid'])->find();
                       //return json($articleInfo);
                       $result['info'][$k]['article']='在文章 《'.$articleInfo['title'].'》留言';
                       //$result['info'][$k]['article']=$articleInfo['title'];
                    }             
                    $result['info'][$k]['addtime']=date("Y-m-d",$v['addtime']);
                    $result['info'][$k]['content']=$v['content'];
                    $result['info'][$k]['openid']=$v['openid'];
                    
                }   
            }
        return json($result);
    }


//留言并增加积分
    public function message(){
        $aid=input('aid');
        $openid=input('openId');
        $name = db('users')->field('username')->where('openid',$openid)->find();
        $name = $name['username'];
        
        $type=8;
        $data=[
            'addtime' => time(),
            'content' => input('content'),
            'name'    => $name,
            'openid'  => $openid,
            'aid'     => input('aid')
        ];
        if(!$aid){
            return json(['status'=>0,'msg'=>'缺少文章参数']);
        }
        if(!$openid){
            return json(['status'=>0,'msg'=>'缺少用户信息']);
        }
        db('message')->insert($data);

        $result=$this->updateScore($openid,$type,$aid);
        $result=json_encode($result,true);
        $result=json_decode($result,true);

        if($result['status']==1){
            $result['msg']='发布留言成功，积分增加成功';
        }
        return json($result);       
    }
//更新积分
    public function updateScore($openid,$type,$aid){
        
        $scoreType=array('超慢跑每日打卡','超慢跑30分钟','超慢跑30-60分钟','超慢跑60分钟以上','自我训练每日打卡','阅读每日打卡','发表感悟','点赞','评论','意见反馈','分享文章');
        if($aid==0){
            $aid='';
        }
        $scoreNum=array(10,5,10,15,10,10,10,5,5,0,5);
        $data['openid']=$openid;
        $data['type']=$type;
        $data['scoretype']=$scoreType[$type];
        $data['date']=time();
        $data['score']=$scoreNum[$type];
        $data['aid']=$aid;

        //return json_encode($data);
        $user = db('users')->field('score')->where('openid', $openid)->find();        
        
        if(!$user){
            return (['status'=>0,'msg'=>'用户不存在，请注册登录!']);
        }else{
            //先更新用户的积分
            db('users')->where('openid',$openid)->setInc('score',$data['score']);
            //再取出用户积分进行比对
            $userScoreUp = db('users')->field('score')->where('openid', $openid)->find();            
            $score=$user['score'];
            $userLevel=db('user_level')->select();

            foreach ($userLevel as $l) {
                if ($score >= $l['bomlimit'] && $score <= $l['toplimit']) {
                    $level = $l['level_id'];
                    break;
                }
            }
            //更新用户等级
            db('users')->where('openid',$openid)->update(['level'=>$level]);
            //return json($level);
            //插入用户的积分记录
            db('user_score')->insert($data);
            return (['status'=>1,'msg'=>'积分已增加','info'=>'增加'.$data['score'].'积分']);
        }
    }

//更新用户信息

    public function upInfo(){
        $openid=input('openId');
        $data['username']=input('nickName');
        $data['avatar']=input('avatarUrl');
        
        //if(db('users')->where(array('openid'=>$openid))->update($data)!==false){
        if(db('users')->where('openid',$openid)->update($data)!==false){

            return json(['status'=>1,'msg'=>'更新成功!']);
        }else{
            return json(['status'=>0,'msg'=>'更新失败!']);
        }        
        
    }

//更新用户信息等级
    public function upLevel($openid){
        $name = db('users')->field('username')->where('openid',$openid)->find();
    }


    public function vget($url)
    {
        $info = curl_init();
        curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($info, CURLOPT_HEADER, 0);
        curl_setopt($info, CURLOPT_NOBODY, 0);
        curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info, CURLOPT_URL, $url);
        $output = curl_exec($info);
        curl_close($info);
        return $output;
    }

    private function token($openid) {
        return md5($openid . 'token_salt');
    }
    public function getToken() {
        return $this->token;
    }
}
