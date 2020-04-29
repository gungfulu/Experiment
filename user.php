<?php

session_start();

function filterWords($str)
{
    $farr = array(
        "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
        "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
        "/select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is"
    );
    $str = preg_replace($farr, '', $str);
    return $str;
}

$do = $_GET['do'];
$dsn = 'mysql:dbname=php;charset=utf8';
$pdo = new PDO($dsn, 'php', '123456');
//This is the PDO error mode attribute. PDO automatically throws an exception
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

switch ($do) {
        // User login
    case "login":
        try {
            $loginName = $_GET['loginName'];
            $loginPwd = $_GET['loginPwd'];

            $loginName = filterWords($loginName);
            $loginPwd = filterWords($loginPwd);

            $loginSql = "select *  from users where username='" . $loginName . "' and pwd='" . $loginPwd . "'";
            //echo $loginSql;

            $res = $pdo->query($loginSql);
            $rowCount =  $res->rowCount();
            foreach ($res as $row) {
                $userType = $row['userType'];
                $userid = $row['id'];
                $_SESSION['userid'] = $userid;
                $_SESSION['userType'] = $userType;
            }
            if ($rowCount) {
                $_SESSION['username'] = $loginName;
                echo '1';
            } else {
                echo '0';
            }
        } catch (PDOException $ex) {
            echo 'error message：' . $ex->getMessage(), '<br>';
            echo 'error file' . $ex->getFile(), '<br>';
            echo 'Wrong line number:' . $ex->getLine();
        }
        break;
        //User Register
    case "Register":

        try {
            $uaerName = $_GET['uaerName'];
            $pwd = $_GET['pwd'];
            $email = $_GET['email'];
            $category = $_GET['category'];

            $uaerName = filterWords($uaerName);
            $pwd = filterWords($pwd);
            $email = filterWords($email);
            $category = filterWords($category);

            $insertSql = "insert into  users values (null,'" . $uaerName . "','" . $pwd . "','" . $category . "','" . $email . "')";
            $flag = $pdo->exec($insertSql);


            if ($flag) {
                echo '1';
            } else {
                echo '0';
            }
        } catch (PDOException $ex) {
            echo 'error message：' . $ex->getMessage(), '<br>';
            echo 'error file' . $ex->getFile(), '<br>';
            echo 'Wrong line number:' . $ex->getLine();
        }

        break;



    case "logout": {
            unset($_SESSION['userid']);
            unset($_SESSION['userType']);
            unset($_SESSION['username']);
            echo '1';
        }

        break;
}