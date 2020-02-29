<?php
$cx16pal ="!word $0000,$0FFF,$0808,$0AFE,$0C4C,$00C5,$000A,$0EE7,$0D85,$0640,$0F77,$0333,$0777,$0AF6,$008F,$0BBB\n";
$cx16pal.="!word $0000,$0111,$0222,$0333,$0444,$0555,$0666,$0777,$0888,$0999,$0AAA,$0BBB,$0CCC,$0DDD,\$OEEE,$0FFF\n";
$cx16pal.="!word $0211,$0433,$0644,$0866,$0A88,$0C99,$0FBB,$0211,$0422,$0633,$0844,$0A55,$0C66,$0F77,$0200,$0411\n";
$cx16pal.="!word $0611,$0822,$0A22,$0C33,$0F33,$0200,$0400,$0600,$0800,$0A00,$0C00,$0F00,$0221,$0443,$0664,$0886\n";
$cx16pal.="!word $0AA8,$0CC9,$0FEB,$0211,$0432,$0653,$0874,$0A95,$0CB6,$0FD7,$0210,$0431,$0651,$0862,$0A82,$0CA3\n";
$cx16pal.="!word $0FC3,$0210,$0430,$0640,$0860,$0A80,$0C90,$0FB0,$0121,$0343,$0564,$0786,$09A8,$0BC9,$0DFB,$0121\n";
$cx16pal.="!word $0342,$0463,$0684,$08A5,$09C6,$0bF7,$0120,$0241,$0461,$0582,$06A2,$08C3,$09F3,$0120,$0240,$0360\n";
$cx16pal.="!word $0480,$05A0,$06C0,$07F0,$0121,$0343,$0465,$0686,$08A8,$09CA,$0BFC,$0121,$0242,$0364,$0485,$05A6\n";
$cx16pal.="!word $06C8,$07F9,$0020,$0141,$0162,$0283,$02A4,$03C5,$03F6,$0020,$0041,$0061,$0082,$00A2,$00C3,$00F3\n";
$cx16pal.="!word $0122,$0344,$0466,$0688,$08AA,$09CC,$0BFF,$0122,$0244,$0366,$0488,$05AA,$06CC,$07FF,$0022,$0144\n";
$cx16pal.="!word $0166,$0288,$02AA,$03CC,$03FF,$0022,$0044,$0066,$0088,$00AA,$00CC,$00FF,$0112,$0334,$0456,$0668\n";
$cx16pal.="!word $088A,$09AC,$0BCF,$0112,$0224,$0346,$0458,$056A,$068C,$079F,$0002,$0114,$0126,$0238,$024A,$035C\n";
$cx16pal.="!word $036F,$0002,$0014,$0016,$0028,$002A,$003C,$003F,$0112,$0334,$0546,$0768,$098A,$0B9C,$0DBF,$0112\n";
$cx16pal.="!word $0324,$0436,$0648,$085A,$096C,$0B7F,$0102,$0214,$0416,$0528,$062A,$083C,$093F,$0102,$0204,$0306\n";
$cx16pal.="!word $0408,$050A,$060C,$070F,$0212,$0434,$0646,$0868,$0A8A,$0C9C,$0FBE,$0211,$0423,$0635,$0847,$0A59\n";
$cx16pal.="!word $0C6B,$0F7D,$0201,$0413,$0615,$0826,$0A28,$0C3A,$0F3C,$0201,$0403,$0604,$0806,$0A08,$0C09,$0F0B";

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
