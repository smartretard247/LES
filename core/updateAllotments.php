<?php $root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'); //get root folder for relative paths
    session_save_path($root . '/sessions'); session_start();
    
    include_once $_SESSION['rootDir'] . '../../database.php'; $db = new Database('les');
    
    $names = array();
    $amounts = array();
    
    for($i = 1; $i <= 6; $i++) {
        if(($amounts[] = (float)filter_input(INPUT_POST, "Amount$i")) != 0) {
            $names[] = filter_input(INPUT_POST, "Name$i");
        } else {
            $names[] = '';
        }
    }
    
    reset($names);
    reset($amounts);
    
    if($_SESSION['error_message'] == '') {
        $success = 0;
        
        for($i = 1; $i <= 6; $i++) {
            $success += $db->SafeExec("UPDATE allotments SET Name = :0, Amount = :1 WHERE ID = :2",array(current($names), current($amounts), $i));
            next($names);
            next($amounts);
        }
        
        if($success) {
            //on successful add goto...
            header("location:../");
            exit();
        } else {
            $_SESSION['error_message'] .= 'You did not make any changes.<br/>';
        }
    } else {
        //$_SESSION['error_message'] = substr($_SESSION['error_message'], 0, strlen($_SESSION['error_message'])-5);
    }
    
    header("location:../");
    exit();
