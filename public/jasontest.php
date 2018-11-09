<?php

$referer = $_SERVER[HTTP_X_FORWARDED_FOR];                                                             
$referer = explode(",",$referer);                                                                      
                                                                                                       
if ($referer[0] == "10.3.215.101") {                                                                   
        // do nothing                                                                                  
} else {                                                                                               
        die('You cannot access this data directly, please use doctorsthatdo.org.');                    
}
?>
