<?php
$cx16pal="!word $0000,\$FF0F,$0008,\$FE0A,$4C0C,\$C500,$0A00,\$E70E,$850D,$4006,$770F,$3303,$7707,\$F60A,$8F00,\$BB0B\n!word $0000,$1101,$2202,$3303,$4404,$5505,$6606,$7707,$8808,$9909,\$AA0A,\$BB0B,\$CC0C,\$DD0D,\$EE0E,\$FF0F\n!word $1102,$3304,$4406,$6608,$880A,$990C,\$BB0F,$1102,$2204,$3306,$4408,$550A,$660C,$770F,$0002,$1104\n!word $1106,$2208,$220A,$330C,$330F,$0002,$0004,$0006,$0008,$000A,$000C,$000F,$2102,$4304,$6406,$8608\n!word \$A80A,\$C90C,\$EB0F,$1102,$3204,$5306,$7408,$950A,\$B60C,\$D70F,$1002,$3104,$5106,$6208,$820A,\$A30C\n!word \$C30F,$1002,$3004,$4006,$6008,$800A,$900C,\$B00F,$2101,$4303,$6405,$8607,\$A809,\$C90B,\$FB0D,$2101\n!word $4203,$6304,$8406,\$A508,\$C609,\$F70B,$2001,$4102,$6104,$8205,\$A206,\$C308,\$F309,$2001,$4002,$6003\n!word $8004,\$A005,\$C006,\$F007,$2101,$4303,$6504,$8606,\$A808,\$CA09,\$FC0B,$2101,$4202,$6403,$8504,\$A605\n!word \$C806,\$F907,$2000,$4101,$6201,$8302,\$A402,\$C503,\$F603,$2000,$4100,$6100,$8200,\$A200,\$C300,\$F300\n!word $2201,$4403,$6604,$8806,\$AA08,\$CC09,\$FF0B,$2201,$4402,$6603,$8804,\$AA05,\$CC06,\$FF07,$2200,$4401\n!word $6601,$8802,\$AA02,\$CC03,\$FF03,$2200,$4400,$6600,$8800,\$AA00,\$CC00,\$FF00,$1201,$3403,$5604,$6806\n!word $8A08,\$AC09,\$CF0B,$1201,$2402,$4603,$5804,$6A05,$8C06,$9F07,$0200,$1401,$2601,$3802,$4A02,$5C03\n!word $6F03,$0200,$1400,$1600,$2800,$2A00,$3C00,$3F00,$1201,$3403,$4605,$6807,$8A09,$9C0B,\$BF0D,$1201\n!word $2403,$3604,$4806,$5A08,$6C09,$7F0B,$0201,$1402,$1604,$2805,$2A06,$3C08,$3F09,$0201,$0402,$0603\n!word $0804,$0A05,$0C06,$0F07,$1202,$3404,$4606,$6808,$8A0A,$9C0C,\$BE0F,$1102,$2304,$3506,$4708,$590A\n!word $6B0C,$7D0F,$0102,$1304,$1506,$2608,$280A,$3A0C,$3C0F,$0102,$0304,$0406,$0608,$080A,$090C,$0B0F";

$pal="";

$entries = scandir("/dev/shm");
$now=time();
foreach ($entries as $entry) {
	if (strpos($entry, "php")!==false) {
		if (($now-filemtime("/dev/shm/$entry"))>15*60) unlink("/dev/shm/$entry");
	}
}

if (isset($_FILES['ftu'])) {
	switch ($_POST['bpp']) {
		case 2: $bpp=2; break;
		case 8: $bpp=8; break;
		default: $bpp=4; break;
	}
	$addr = $_POST['addr'];
	if ($addr != "") {
		$addr = @hexdec($addr);
		if (($addr > 0xFFFF) || ($addr < 0)) $addr=0;
	} else $addr=0;

	$fname = $_FILES['ftu']['name'];
	$tmpname = $_FILES['ftu']['tmp_name'];
	$fsize = $_FILES['ftu']['size'];

	switch ($_FILES['ftu']['error']) {
		case 1:
		case 2: dohtml("","","","File too large"); die();
		case 3: dohtml("","","","Partial upload"); die();
		case 4: dohtml("","","","No file was uploaded"); die();
		case 6:
		case 7:
		case 8: dohtml("","","","An error occurred"); die();
	}
	if ($fsize > 1073741824) { dohtml("","","","File too large"); die(); }
	if (mime_content_type($tmpname) != "image/png") { dohtml("","","","Not a png file"); die(); }

	if (move_uploaded_file($tmpname, "/dev/shm/".basename($tmpname))==false) { dohtml("","","","Unable to move file");die(); }

	$tmpname="/dev/shm/".basename($tmpname);

	$bname = explode(".", $fname);
	$bname = $bname[0].".bin";

	$def = doconv($tmpname, $bpp, $addr);

	dohtml($def, $pal, basename($tmpname)."&n=".$bname);
	flush();

} else dohtml();

function doconv($tmpfile, $bpp, $addr) {
	global $pal, $cx16pal;

	if (($img = imagecreatefrompng($tmpfile))==false) { dohtml("","","","Could not read image file"); die(); }

	if (imageistruecolor($img)) {
		switch ($bpp) {
			case 2: $numcols=4; break;
			case 8: $numcols=256; break;
			default: $numcols=16; break;
		}
		ImageTrueColorToPalette2($img, FALSE, $numcols);
	}

	if (($fp = fopen("/dev/shm/".basename($tmpfile), "wb"))==false) {
		dohtml("","","","Could not create output file");
		die();
	}

	writeimgbin($fp, $img, $bpp, $addr);
	fclose($fp);

	$pal = createpalette($img);
	if (strcmp($pal, $cx16pal)==0) return true; else return false;
}

function dohtml($def="", $pal="", $fnam="", $err="") {
 global $cx16pal;
 $num=trim(@file_get_contents("served.txt")); ?>
<html>
<head>
<title>Png to bin converter</title>
</head>
<body>
<img style='float:right;' src="cx16p2b.png" />
<h1>Png to Bin converter</h1>
<div><h2 style='display:inline;'>Created for the Commander X16</h2><p style='display:inline;'><?php echo " - $num binaries served since Januarry 30, 2020"; ?></p></div>
<hr>
<p>
This page can convert a png file to a binary file that can be used in the CommanderX16.<br>
For full control of palette/colors, be sure to store your image as indexed instead of truecolor.</p>
<p>If the image is stored as truecolor, this page will try to convert it to indexed based on your selection of bits per pixel.<br>
If you do not want to change the default palette on the CX16, be sure to import the <a href="cx16_palette.gpl" download >palette from here</a>, into your drawing program.<br>
If you do want to change palette, it is recommended to store the entire 4, 16 or 256 color palette with the image.
</p>
<ul>
<li>In 2bpp mode, only the first 4 colors are used
<li>In 4bpp mode, only the 16 first colors are used
<li>In 8bpp mode, alle 256 colors are used
</ul>
<form action="." method="post" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="1073741824" />
<select name="bpp">
 <option value="2">2 bpp (4 colors)</option>
 <option value="4" selected >4 bpp (16 colors)</option>
 <option value="8">8 bpp (256 colors)</option>
</select>
<input type="file" name="ftu" />
<input type="submit" value="Upload image" name="submit"><br>
Address: <input style="text-align:right;" size=4 type="text" name="addr" value="0000"/> (ex. 4000 to store at address $x4000 in VRAM where x is the banknumber chosen by LOAD command)<br>
If you leave the field empty, $0000 is inserted.<br>
</form>
<?php
if ($fnam != "") echo "<p><a href=\"getfile.php?f=$fnam\">Get your file</a></p>";
if ($err != "") echo "<p><font color=red>$err</font></p>\n"; ?>
<p>To load your bin file into Video RAM (VRAM), you can use the <a href="https://cx16.dk/c64-kernal-routines/load.html" target="_new">LOAD</a> kernal API call.<br>
In the CX16, it has been modified to be able to load data directly into VRAM. Before calling LOAD, you must call <a href="https://cx16.dk/c64-kernal-routines/setlfs.html" target="_new">SETLFS</a> and <a href="https://cx16.dk/c64-kernal-routines/setnam.html" target="_new">SETNAM</a></p>
<p>You can find an example of loading a 4bpp 320x240 image <a href="https://gist.github.com/JimmyDansbo/f955378ee4f1087c2c286fcd6956e223" target="_new">here</a>.</p>
<p>For sprite-/tile-maps, you should create an image with the width of your sprite/tile and then place the sprites/tiles vertically.</p>
<?php if ($def===true) echo "<p><font color=lightgreen>Image uses default palette.</font></p>";
 elseif ($def===false) echo "<p><b><font color=green>Image palette below:</font></b></p><pre style='background-color:lightgray;'>$pal</pre>"; ?>
<p><b>Default Commander X16 palette as stored in memory:</b></p>
<pre style='background-color:lightgray'><?php echo $cx16pal; ?></pre>
<p>The palette can be pasted directly into your program and then the program can copy it to VERA at address $F1000<br>
Remember to set a label before pasting the palette.</p>
</body>
</html>
<?php }


function ImageTrueColorToPalette2($image, $dither, $ncolors) {
    $width = imagesx($image);
    $height = imagesy($image);
    $colors_handle = ImageCreateTrueColor($width, $height);
    ImageCopyMerge($colors_handle, $image, 0, 0, 0, 0, $width, $height, 100);
    ImageTrueColorToPalette($image, $dither, $ncolors);
    ImageColorMatch($colors_handle, $image);
    ImageDestroy($colors_handle);
}

function rgb24to12($image, $colorindex) {
	if (($rgb = @imagecolorsforindex($image, $colorindex))) {
		$red = ($rgb['red']>>4);
		$green = ($rgb['green']>>4);
		$blue = ($rgb['blue']>>4);

		$retstr=sprintf("$%02X%02X", (($green&0x0f)<<4)|($blue&0x0f),(0x0f&$red));

		return $retstr;
	} else return FALSE;
}

function createpalette($img) {
	$cnt=0;
	$str = "!word ";
	while (($val=rgb24to12($img, $cnt))) {
		$str .= "$val";
		$cnt++;
		if (($cnt%16)==0) {
			$str .= "\n!word ";
		} else $str .= ",";
	}
	return rtrim($str, "\n!word ,");
}

function writeimgbin($fp, $img, $bpp=4, $addr=0) {
	fwrite($fp, pack("v", $addr), 2);
	$pixnum=0;
	for ($y=0; $y<imagesy($img); $y++) {
		for ($x=0; $x<imagesx($img); $x++) {
			switch ($bpp) {
				case 2:	switch ($pixnum) {
						case 0:	$pix = (imagecolorat($img,$x,$y)&0x03)<<6;
							$pixnum++;
							break;
						case 1:	$pix = ($pix|((imagecolorat($img,$x,$y)&0x03)<<4));
							$pixnum++;
							break;
						case 2: $pix = ($pix|((imagecolorat($img,$x,$y)&0x03)<<2));
							$pixnum++;
							break;
						case 3: $pix = ($pix|(imagecolorat($img,$x,$y)&0x03));
							fwrite($fp, pack("C",$pix),1);
							$pixnum=0;
							break;
					}
					break;
				case 8:	fwrite($fp, pack("C", imagecolorat($img, $x, $y)), 1); break;
				default:if ($pixnum==0) {
						$pix = (imagecolorat($img, $x, $y)&0x0f)<<4;
						$pixnum++;
					} elseif ($pixnum==1) {
						$pix = $pix | (imagecolorat($img,$x,$y)&0x0f);
						fwrite($fp, pack("C",$pix),1);
						$pixnum=0;
					}
					break;
			}
		}
	}
}
?>
