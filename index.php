<?php

    require 'vendor/autoload.php';

    //Uri 파싱작업
    function processInput($uri){
        $uri = implode('/',
            array_slice(
                explode('/', $_SERVER['REQUEST_URI']), 2));
            return $uri;
    }

    //parameter로 전달된 값을 json으로 출력
    function processOutput($response){
        echo json_encode($response, JSON_UNESCAPED_UNICODE);                              //json 유니코드 인코딩 필요
    }

    //mysql 접속 위한 PDO 객체 선언하는 메서드
    function getPDOInstance(){
            return new PDO('mysql:host=localhost;dbname=realies_alpha; charset=utf8', 'root', 'teamnova');
    }

    //router 객체 선언
    $router = new Phroute\RouteCollector();

    $router->get('/', function(){
            return 'Hello, PHRoute!';
    });

    $router->get('/hello', function(){
            return 'Hello!';
    });

    $router->get('/3Dmodel/{ModelNum}', function($ModelNum){

            $ResAddress=$_SERVER['SERVER_NAME']."/vrssample";


            $db = getPDOInstance();                                                       //DB접속 위한 PDO객체 선언
            $sql = "SELECT * FROM new_model_table WHERE ModelNum = :ModelNum";            //쿼리문
            $st = $db->prepare($sql, array(PDO::ATTR_CURSOR=> PDO::CURSOR_FWDONLY));
            $st->execute(array(":ModelNum"=>$ModelNum));                                  //인자값 전달
            $result = $st->fetchAll(PDO::FETCH_CLASS);

            $ResultArray = array();

            foreach ($result as $modeldata) {
              // code...
              $ModelNum = $modeldata->ModelNum;
              $ModelDirname=$modeldata->ModelDirname;
              $ModelName=$modeldata->ModelName;
              $ModelFilename=$modeldata->ModelFilename;
              $ModelColorwayNum=$modeldata->ModelColorwayNum;

              $ColorwayMTL = array();

              for ($i = 0 ; $i<$ModelColorwayNum ; $i++) {
                $MTLFilename="";

                if($ModelColorwayNum==1){
                  $MTLFilename=$ModelFilename.".mtl";
                }
                else{
                  $MTLFilename=$ModelFilename."_".$i.".mtl";
                }

                array_push($ColorwayMTL,
                      array('colorway_'.$i => $ResAddress."/upload/".$ModelDirname."/".$MTLFilename)
                );
              }

              array_push($ResultArray,
                    array('ModelNum' => $ModelNum,
                          'ModelDirname' => $ModelDirname,
                          'ModelName' => $ModelName,
                          'ModelColorwayNum' => $ModelColorwayNum,
                          'Thumbnail_URL' => $ResAddress."/upload/".$ModelDirname."/thumbnail/",
                          'Obj_URL' => $ResAddress."/upload/".$ModelDirname."/".$ModelFilename.".obj",
                          'Mtl_URL' => $ColorwayMTL
              ));
            }

            return $ResultArray;                                                               //쿼리 결과값 리턴
    });

    $router->get('/modelList/{StartNum:i}/{DataCount:i}', function($StartNum, $DataCount){
            $ResAddress=$_SERVER['SERVER_NAME']."/vrssample";
            $min = $StartNum-1;

            $db = getPDOInstance();
            $sql = "SELECT ModelNum, ModelCat, ModelDirname, ModelName, ModelFilename, ModelColorwayNum
                    FROM new_model_table
                    ORDER BY ModelRegistDateTime DESC
                    LIMIT ".$min." , ".$DataCount;                                         //쿼리문

            $st = $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);             //에러로그 출력 설정
            $st = $db->prepare($sql, array(PDO::ATTR_CURSOR=> PDO::CURSOR_FWDONLY));
            $st->execute();                                                               //인자값 전달
            $result = $st->fetchAll(PDO::FETCH_CLASS);

            $ResultArray=array();

            foreach ($result as $modeldata) {
              // code...
              $ModelNum = $modeldata->ModelNum;
              $ModelDirname=$modeldata->ModelDirname;
              $ModelName=$modeldata->ModelName;
              $ModelFilename=$modeldata->ModelFilename;
              $ModelColorwayNum=$modeldata->ModelColorwayNum;

              $ColorwayMTL = array();

              for ($i = 0 ; $i<$ModelColorwayNum ; $i++) {
                $MTLFilename="";

                if($ModelColorwayNum==1){
                  $MTLFilename=$ModelFilename.".mtl";
                }
                else{
                  $MTLFilename=$ModelFilename."_".$i.".mtl";
                }

                array_push($ColorwayMTL,
                      array('colorway_'.$i => $ResAddress."/upload/".$ModelDirname."/".$MTLFilename)
                );
              }

              array_push($ResultArray,
                    array('ModelNum' => $ModelNum,
                          'ModelDirname' => $ModelDirname,
                          'ModelName' => $ModelName,
                          'ModelColorwayNum' => $ModelColorwayNum,
                          'Thumbnail_URL' => $ResAddress."/upload/".$ModelDirname."/thumbnail/",
                          'Obj_URL' => $ResAddress."/upload/".$ModelDirname."/".$ModelFilename.".obj",
                          'Mtl_URL' => $ColorwayMTL
              ));
            }

            return $ResultArray;                                                               //쿼리 결과값 리턴
    });

    //dispatcher 객체 선언 :
    $dispatcher = new Phroute\Dispatcher($router);

    try {

      //echo $_SERVER['REQUEST_URI'];                                                     //테스트 출력
      //echo processInput($_SERVER['REQUEST_URI']);
      //Request Method와 URI를 받아 매칭되는 route를 호출한다. 매칭되는 route가 없을시 아래와 같은 exception을 발생 시킨다.
      $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], processInput($_SERVER['REQUEST_URI']));

    } catch (Phroute\Exception\HttpRouteNotFoundException $e) {

      var_dump($e);
      die();

    } catch (Phroute\Exception\HttpMethodNotAllowedException $e) {

      var_dump($e);
      die();

    }

    //응답값을 json으로 출력
    processOutput($response);

?>
