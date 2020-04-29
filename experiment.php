<?php

session_start();

$do = $_GET['do'];
$dsn = 'mysql:dbname=php;charset=utf8';
$pdo = new PDO($dsn, 'php', '123456');
$filename1 = $_FILES['myInputFile']['name'];
//This is the PDO error mode attribute. PDO automatically throws an exception
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

switch ($do) {

        //Add an experiment application
    case "add": {
            //Get the file upload code. 0 means the file is uploaded successfully
            $error = $_FILES['myInputFile']['error'];
            echo     $error . "<br/>";
            if ($error === 0) {
                $filename = $_FILES['myInputFile']['name'];
                //Get file temporary path
                $temp_name = $_FILES['myInputFile']['tmp_name'];
                //Get size
                $size = $_FILES['myInputFile']['size'];
                $arr = pathinfo($filename);
                //Get the suffix of the file
                $ext_suffix = $arr['extension'];
                //Check whether the path to store the uploaded file exists. If not, create a new directory
                if (!file_exists('uploads')) {
                    mkdir('uploads');
                }
                //Create a new name for the uploaded file to ensure more security
                $new_filename = "uploads/" . date('YmdHis', time()) . rand(100, 1000) . $ext_suffix;
                echo   $new_filename . "<br/>";
                echo   $filename . "<br/>";
                echo   $ext_suffix . "<br/>";
                //Move files from temporary path to disk
                $flag = move_uploaded_file($temp_name, $new_filename);
                if ($flag) {
                    try {
                        //Add attachment table
                        $insertSql = "insert into  exper_file values (null,'" . $filename1 . "','" .  $new_filename  . "')";
                        $file_flag = $pdo->exec($insertSql);
                        //Add experiment table
                        $mytTitle = $_POST['mytTitle'];
                        $myContent = $_POST['myContent'];
                        // fielID
                        $fileId = $pdo->lastInsertId();
                        $username = $_SESSION['username'];
                        $experiment = "insert into  experiment values (null,'" . $mytTitle . "','" . $myContent . "','" . $username . "','" . $fileId . "','Not approved', NOW() )";
                        echo   $insertSql . "<br/>";
                        echo   $experiment . "<br/>";

                        $ep_flag = $pdo->exec($experiment);
                        if ($flag && $ep_flag) {
                            header('location:admin.html');
                        } else {
                            echo 'error';
                        }
                    } catch (PDOException $ex) {
                        echo 'error message：' . $ex->getMessage(), '<br>';
                        echo 'error file' . $ex->getFile(), '<br>';
                        echo 'Wrong line number:' . $ex->getLine();
                    }
                }
            }
        }
        break;
        //Modify experiment application
    case "edit": {
            if (!empty($_FILES['editModalInputFile']['tmp_name'])) {
                $filename = $_FILES['editModalInputFile']['name'];
                //Get file temporary path
                $temp_name = $_FILES['editModalInputFile']['tmp_name'];
                //Get size
                $size = $_FILES['editModalInputFile']['size'];
                $arr = pathinfo($filename);
                //Get the suffix of the file
                $ext_suffix = $arr['extension'];
                //Create a new name for the uploaded file to ensure more security
                $new_filename = "uploads/" . date('YmdHis', time()) . rand(100, 1000) . $ext_suffix;
                //Move files from temporary path to disk
                $flag = move_uploaded_file($temp_name, $new_filename);
                if ($flag) {
                    try {
                        //Add attachment table
                        $insertSql = "insert into  exper_file values (null,'" . $filename1 . "','" .  $new_filename  . "')";
                        $file_flag = $pdo->exec($insertSql);
                        //Add experiment table
                        $mytTitle = $_POST['editModalTitle'];
                        $myContent = $_POST['editModalContent'];
                        $editdateid = $_POST['editdateid'];
                        // fielID
                        $fileId = $pdo->lastInsertId();
                        $updateSql = "update  experiment   set Title='" . $mytTitle . "',content='" . $myContent . "' ,fileid=" . $fileId . " where id=" . $editdateid . "";
                        $ep_flag = $pdo->exec($updateSql);
                        header('location:admin.html');
                    } catch (PDOException $ex) {
                        echo 'error message：' . $ex->getMessage(), '<br>';
                        echo 'error file' . $ex->getFile(), '<br>';
                        echo 'Wrong line number:' . $ex->getLine();
                    }
                }
            } else {
                try {
                    $mytTitle = $_POST['editModalTitle'];
                    $myContent = $_POST['editModalContent'];
                    $editdateid = $_POST['editdateid'];
                    $updateSql = "update  experiment   set Title='" . $mytTitle . "',content='" . $myContent . "'  where id=" . $editdateid . "";
                    $ep_flag = $pdo->exec($updateSql);
                    header('location:admin.html');
                } catch (PDOException $ex) {
                    echo 'error message：' . $ex->getMessage(), '<br>';
                    echo 'error file' . $ex->getFile(), '<br>';
                    echo 'Wrong line number:' . $ex->getLine();
                }
            }
        }


        break;
        //Initialize list page
    case "initMainPage": {
            try {

                $username = $_SESSION['username'];
                $userType = $_SESSION['userType'];
                $userid = $_SESSION['userid'];

                switch ($userType) {

                    case 1: {;
                            $selectSql = "SELECT  e.`id`,e.`Title`,e.`content`,e.`username`,e.`fileid`,e.`Approved` ,e.`apptime` FROM experiment e   WHERE id  not in (select experId from assign  )";
                        }
                        break;

                    case 3: {

                            $selectSql = "  SELECT e.`id`,e.`Title`,e.`content`,e.`username`,e.`fileid`,
                                            e.`Approved`,e.`apptime` 
                                          FROM experiment e, assign a 
                                            WHERE Approved = 'Not approved' 
                                                and e.id = a.experId 
                                                and a.userid =" . $userid;
                        }
                        break;

                    default: {

                            $selectSql = "SELECT  e.`id`,e.`Title`,e.`content`,e.`username`,e.`fileid`,e.`Approved` ,e.`apptime` FROM experiment e   WHERE username='" . $username . "'";
                        }
                }

                $stmt = $pdo->query($selectSql);
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($rs);
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;
        //Get the list of reviewers

    case "getAuditor": {
            try {
                $selectSql = "SELECT id,username FROM  users  WHERE  userType=3";
                $stmt = $pdo->query($selectSql);
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($rs);
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;
        //Get the details of the experiment application
    case "getep": {
            try {
                $dataid = $_GET['dataid'];
                $selectSql = "SELECT  e.`Title`,e.`content`, f.`filePath`,f.`fileName`  FROM  experiment e,exper_file  f   WHERE f.`id`=e.`fileid` AND e.`id`=" .  $dataid;
                $stmt = $pdo->query($selectSql);
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($rs);
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;
        //Get the user category of the current login
    case "initrule": {
            try {
                $username = $_SESSION['username'];
                $selectSql = "SELECT  userType   FROM  users  WHERE  username ='$username'";
                $stmt = $pdo->query($selectSql);
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($rs);
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;
        //Review the experiment application
    case "audit": {
            try {
                $username = $_SESSION['username'];
                $auditdateid = $_GET['auditdateid'];
                $auditContent = $_GET['auditContent'];
                $isagree = $_GET['isagree'];

                $insertSql = "INSERT INTO auditRecord  VALUES (NULL," . $auditdateid . ",'" . $username . "','" . $auditContent . "',NOW())";

                $updateSql = "UPDATE  experiment  e  SET e.`Approved`='" . $isagree . "' WHERE id=" . $auditdateid;

                //echo   $insertSql;
                //echo   $updateSql;

                $pdo->exec($insertSql);
                $pdo->exec($updateSql);
                echo  1;
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;


        //Get approval records
    case "getauditRecord": {
            try {
                $dataid = $_GET['dataid'];
                $selectSql = "SELECT  e.`Title` ,e.`username`,a.`auditContent`,a.`audittime` FROM  auditRecord  a  ,experiment e  WHERE e.`id`=a.`experid` AND e.`id`=" . $dataid . "";
                $stmt = $pdo->query($selectSql);
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($rs);
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;
        //Delete experiment application data

    case "delete": {
            try {
                $id = $_GET['id'];
                $delateSql = "delete  from experiment  where id=" . $id . "";
                $pdo->exec($delateSql);
                echo 1;
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;

    case "assign": {
            try {
                $assigndateid = $_GET['assigndateid'];
                $assignReviewer = $_GET['assignReviewer'];


                $assignReviewerarray = explode(",", $assignReviewer);

                foreach ($assignReviewerarray as $value) {
                    // echo $value;
                    if ($value) {
                        $insertSql = "insert into assign values (null," . $assigndateid . "," . $value . ")";
                        $pdo->exec($insertSql);
                        //echo $insertSql;
                    }
                }

                // $delateSql = "insert into assign (null,experId,userid)";
                // $pdo->exec($delateSql);
                echo 1;
            } catch (PDOException $ex) {
                echo 'error message：' . $ex->getMessage(), '<br>';
                echo 'error file' . $ex->getFile(), '<br>';
                echo 'Wrong line number:' . $ex->getLine();
            }
        }
        break;
}