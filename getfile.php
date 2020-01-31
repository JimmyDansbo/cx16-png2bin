<?php

 if (file_exists("/dev/shm/".basename($_GET['f']))) {
 	$num=trim(@file_get_contents("served.txt"));
 	$num++;
 	@file_put_contents("served.txt", $num);
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment;filename="'.$_GET['n'].'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: '.filesize("/dev/shm/".basename($_GET['f'])));
	flush();
	readfile("/dev/shm/".basename($_GET['f']));
	unlink("/dev/shm/".basename($_GET['f']));
} else {
?>
<html><body><center><h1 style='color:red;'>Error!! File no longer exists</h1></center></body></html>
<?php
}
?>
