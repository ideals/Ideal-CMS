<?php
// -----------------------------------------------
// Cryptographp v1.4
// (c) 2006-2007 Sylvain BRISON 
//
// www.cryptographp.com 
// cryptographp@alphpa.com 
//
// Licence CeCILL modifi�e
// => Voir fichier Licence_CeCILL_V2-fr.txt)
// -----------------------------------------------
if (!defined('DOCUMENT_ROOT')) {
    define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);
}

// -------------------------------------
// Configuration du fond du cryptogramme
// -------------------------------------

$cryptwidth = 130; // Largeur du cryptogramme (en pixels)
$cryptheight = 40; // Hauteur du cryptogramme (en pixels)

$bgR = 255; // Couleur du fond au format RGB: Red (0->255)
$bgG = 255; // Couleur du fond au format RGB: Green (0->255)
$bgB = 255; // Couleur du fond au format RGB: Blue (0->255)

$bgclear = false; // Fond transparent (true/false)
// Uniquement valable pour le format PNG

$bgimg = ''; // Le fond du cryptogramme peut-�tre une image
// PNG, GIF ou JPG. Indiquer le fichier image
// Exemple: $fondimage = 'photo.gif';
// L'image sera redimensionn�e si n�cessaire
// pour tenir dans le cryptogramme.
// Si vous indiquez un r�pertoire plut�t qu'un
// fichier l'image sera prise au hasard parmi
// celles disponibles dans le r�pertoire

$bgframe = true; // Ajoute un cadre de l'image (true/false)


// ----------------------------
// Configuration des caract�res
// ----------------------------

// Couleur de base des caract�res

$charR = 0; // Couleur des caract�res au format RGB: Red (0->255)
$charG = 0; // Couleur des caract�res au format RGB: Green (0->255)
$charB = 0; // Couleur des caract�res au format RGB: Blue (0->255)

$charcolorrnd = true; // Choix al�atoire de la couleur.
$charcolorrndlevel = 2; // Niveau de clart� des caract�res si choix al�atoire (0->4)
// 0: Aucune s�lection
// 1: Couleurs tr�s sombres (surtout pour les fonds clairs)
// 2: Couleurs sombres
// 3: Couleurs claires
// 4: Couleurs tr�s claires (surtout pour fonds sombres)

$charclear = 0; // Intensit� de la transparence des caract�res (0->127)
// 0=opaques; 127=invisibles
// interessant si vous utilisez une image $bgimg
// Uniquement si PHP >=3.2.1

// Polices de caract�res

//$tfont[] = 'alanden.ttf';       // Les polices seront al�atoirement utilis�es.
//$tfont[] = 'bsurp.ttf';       // Vous devez copier les fichiers correspondants
//$tfont[] = 'elecha.ttf';       // sur le serveur.
$tfont[] = 'luggerbu.ttf'; // Ajoutez autant de lignes que vous voulez
//$tfont[] = 'rascal.ttf';       // Respectez la casse ! 
$tfont[] = 'scrawl.ttf';
//$tfont[] = 'wavy.ttf';   
//$tfont[] = 'verdana.ttf';

// Caracteres autoris�s
// Attention, certaines polices ne distinguent pas (ou difficilement) les majuscules 
// et les minuscules. Certains caract�res sont faciles � confondre, il est donc
// conseill� de bien choisir les caract�res utilis�s.

$charel = '012345689'; // Caract�res autoris�s

$crypteasy = false; // Cr�ation de cryptogrammes "faciles � lire" (true/false)
// compos�s alternativement de consonnes et de voyelles.

$charelc = 'BCDFGKLMPRTVWXZ'; // Consonnes utilis�es si $crypteasy = true
$charelv = 'AEIOUY'; // Voyelles utilis�es si $crypteasy = true

$difuplow = false; // Diff�rencie les Maj/Min lors de la saisie du code (true, false)

$charnbmin = 6; // Nb minimum de caracteres dans le cryptogramme
$charnbmax = 6; // Nb maximum de caracteres dans le cryptogramme

$charspace = 18; // Espace entre les caracteres (en pixels)
$charsizemin = 10; // Taille minimum des caract�res
$charsizemax = 18; // Taille maximum des caract�res

$charanglemax = 0; // Angle maximum de rotation des caracteres (0-360)
$charup = false; // D�placement vertical al�atoire des caract�res (true/false)

// Effets suppl�mentaires

$cryptgaussianblur = false; // Transforme l'image finale en brouillant: m�thode Gauss (true/false)
// uniquement si PHP >= 5.0.0
$cryptgrayscal = false; // Transforme l'image finale en d�grad� de gris (true/false)
// uniquement si PHP >= 5.0.0

// ----------------------
// Configuration du bruit
// ----------------------

$noisepxmin = 0; // Bruit: Nb minimum de pixels al�atoires
$noisepxmax = 0; // Bruit: Nb maximum de pixels al�atoires

$noiselinemin = 0; // Bruit: Nb minimum de lignes al�atoires
$noiselinemax = 0; // Bruit: Nb maximum de lignes al�atoires

$nbcirclemin = 0; // Bruit: Nb minimum de cercles al�atoires
$nbcirclemax = 0; // Bruit: Nb maximim de cercles al�atoires

$noisecolorchar = 2; // Bruit: Couleur d'ecriture des pixels, lignes, cercles:
// 1: Couleur d'�criture des caract�res
// 2: Couleur du fond
// 3: Couleur al�atoire

$brushsize = 1; // Taille d'ecriture du princeaiu (en pixels)
// de 1 � 25 (les valeurs plus importantes peuvent provoquer un
// Internal Server Error sur certaines versions de PHP/GD)
// Ne fonctionne pas sur les anciennes configurations PHP/GD

$noiseup = false; // Le bruit est-il par dessus l'ecriture (true) ou en dessous (false)

// --------------------------------
// Configuration syst�me & s�curit�
// --------------------------------

$cryptformat = "jpg"; // Format du fichier image g�n�r� "GIF", "PNG" ou "JPG"
// Si vous souhaitez un fond transparent, utilisez "PNG" (et non "GIF")
// Attention certaines versions de la bibliotheque GD ne gerent pas GIF !!!

$cryptsecure = "md5"; // M�thode de crytpage utilis�e: "md5", "sha1" ou "" (aucune)
// "sha1" seulement si PHP>=4.2.0
// Si aucune m�thode n'est indiqu�e, le code du cyptogramme est stock�
// en clair dans la session.

$cryptusetimer = 0; // Temps (en seconde) avant d'avoir le droit de reg�n�rer un cryptogramme

$cryptusertimererror = 3; // Action � r�aliser si le temps minimum n'est pas respect�:
// 1: Ne rien faire, ne pas renvoyer d'image.
// 2: L'image renvoy�e est "images/erreur2.png" (vous pouvez la modifier)
// 3: Le script se met en pause le temps correspondant (attention au timeout
//    par d�faut qui coupe les scripts PHP au bout de 30 secondes)
//    voir la variable "max_execution_time" de votre configuration PHP

$cryptusemax = 1000; // Nb maximum de fois que l'utilisateur peut g�n�rer le cryptogramme
// Si d�passement, l'image renvoy�e est "images/erreur1.png"
// PS: Par d�faut, la dur�e d'une session PHP est de 180 mn, sauf si
// l'hebergeur ou le d�veloppeur du site en ont d�cid� autrement...
// Cette limite est effective pour toute la dur�e de la session.

$cryptoneuse = false; // Si vous souhaitez que la page de verification ne valide qu'une seule
// fois la saisie en cas de rechargement de la page indiquer "true".
// Sinon, le rechargement de la page confirmera toujours la saisie.


error_reporting(E_ALL ^ E_NOTICE);
srand((double)microtime() * 1000000);

session_start();

$path = pathinfo($_SERVER['SCRIPT_NAME']);
$folder = DOCUMENT_ROOT . $path['dirname'] . '/';


// V�rifie si l'utilisateur a le droit de (re)g�n�rer un cryptogramme
if ($_SESSION['cryptcptuse'] >= $cryptusemax) {
    header("Content-type: image/png");
    readfile($folder . 'images/erreur1.png');
    exit;
}

$delai = time() - $_SESSION['crypttime'];
if ($delai < $cryptusetimer) {
    switch ($cryptusertimererror) {
        case 2  :
            header("Content-type: image/png");
            readfile($folder . 'images/erreur2.png');
            exit;
        case 3  :
            sleep($cryptusetimer - $delai);
            break; // Fait une pause
        case 1  :
        default :
            exit; // Quitte le script sans rien faire
    }
}

// Cr�ation du cryptogramme temporaire
$imgtmp = imagecreatetruecolor($cryptwidth, $cryptheight);
$blank = imagecolorallocate($imgtmp, 255, 255, 255);
$black = imagecolorallocate($imgtmp, 0, 0, 0);
imagefill($imgtmp, 0, 0, $blank);


$word = '';
$x = 10;
$pair = rand(0, 1);
$charnb = rand($charnbmin, $charnbmax);
for ($i = 1; $i <= $charnb; $i++) {
    $tword[$i]['font'] = $tfont[array_rand($tfont, 1)];
    $tword[$i]['angle'] = (rand(1, 2) == 1) ? rand(0, $charanglemax) : rand(360 - $charanglemax, 360);

    if ($crypteasy) $tword[$i]['element'] = (!$pair) ? $charelc{rand(0, strlen($charelc) - 1)} : $charelv{rand(0, strlen($charelv) - 1)};
    else $tword[$i]['element'] = $charel{rand(0, strlen($charel) - 1)};

    $pair = !$pair;
    $tword[$i]['size'] = rand($charsizemin, $charsizemax);
    $tword[$i]['y'] = ($charup ? ($cryptheight / 2) + rand(0, ($cryptheight / 5)) : ($cryptheight / 1.5));
    $word .= $tword[$i]['element'];

    $lafont = $folder . "fonts/" . $tword[$i]['font'];
    imagettftext($imgtmp, $tword[$i]['size'], $tword[$i]['angle'], $x, $tword[$i]['y'], $black, $lafont, $tword[$i]['element']);

    $x += $charspace;
}

// Calcul du racadrage horizontal du cryptogramme temporaire
$xbegin = 0;
$x = 0;
while (($x < $cryptwidth)and(!$xbegin)) {
    $y = 0;
    while (($y < $cryptheight)and(!$xbegin)) {
        if (imagecolorat($imgtmp, $x, $y) != $blank) $xbegin = $x;
        $y++;
    }
    $x++;
}

$xend = 0;
$x = $cryptwidth - 1;
while (($x > 0)and(!$xend)) {
    $y = 0;
    while (($y < $cryptheight)and(!$xend)) {
        if (imagecolorat($imgtmp, $x, $y) != $blank) $xend = $x;
        $y++;
    }
    $x--;
}

$xvariation = round(($cryptwidth / 2) - (($xend - $xbegin) / 2));
imagedestroy($imgtmp);


// Cr�ation du cryptogramme d�finitif
// Cr�ation du fond
$img = imagecreatetruecolor($cryptwidth, $cryptheight);

if ($bgimg and is_dir($bgimg)) {
    $dh = opendir($bgimg);
    while (false !== ($filename = readdir($dh)))
        if (eregi(".[gif|jpg|png]$", $filename)) $files[] = $filename;
    closedir($dh);
    $bgimg = $bgimg . '/' . $files[array_rand($files, 1)];
}
if ($bgimg) {
    list($getwidth, $getheight, $gettype, $getattr) = getimagesize($bgimg);
    switch ($gettype) {
        case "1":
            $imgread = imagecreatefromgif($bgimg);
            break;
        case "2":
            $imgread = imagecreatefromjpeg($bgimg);
            break;
        case "3":
            $imgread = imagecreatefrompng($bgimg);
            break;
    }
    imagecopyresized($img, $imgread, 0, 0, 0, 0, $cryptwidth, $cryptheight, $getwidth, $getheight);
    imagedestroy($imgread);
} else {
    $bg = imagecolorallocate($img, $bgR, $bgG, $bgB);
    imagefill($img, 0, 0, $bg);
    if ($bgclear) imagecolortransparent($img, $bg);
}


function ecriture()
{
    global $folder;
// Cr�ation de l'�criture
    global $img, $ink, $charR, $charG, $charB, $charclear, $xvariation, $charnb, $charcolorrnd, $charcolorrndlevel, $tword, $charspace;
    if (function_exists('imagecolorallocatealpha')) $ink = imagecolorallocatealpha($img, $charR, $charG, $charB, $charclear);
    else $ink = imagecolorallocate($img, $charR, $charG, $charB);

    $x = $xvariation;
    for ($i = 1; $i <= $charnb; $i++) {

        if ($charcolorrnd) { // Choisit des couleurs au hasard
            $ok = false;
            do {
                $rndR = rand(0, 255);
                $rndG = rand(0, 255);
                $rndB = rand(0, 255);
                $rndcolor = $rndR + $rndG + $rndB;
                switch ($charcolorrndlevel) {
                    case 1  :
                        if ($rndcolor < 200) $ok = true;
                        break; // tres sombre
                    case 2  :
                        if ($rndcolor < 400) $ok = true;
                        break; // sombre
                    case 3  :
                        if ($rndcolor > 500) $ok = true;
                        break; // claires
                    case 4  :
                        if ($rndcolor > 650) $ok = true;
                        break; // tr�s claires
                    default :
                        $ok = true;
                }
            } while (!$ok);

            if (function_exists('imagecolorallocatealpha')) $rndink = imagecolorallocatealpha($img, $rndR, $rndG, $rndB, $charclear);
            else $rndink = imagecolorallocate($img, $rndR, $rndG, $rndB);
        }

        $lafont = $folder . "fonts/" . $tword[$i]['font'];
        imagettftext($img, $tword[$i]['size'], $tword[$i]['angle'], $x, $tword[$i]['y'], $charcolorrnd ? $rndink : $ink, $lafont, $tword[$i]['element']);

        $x += $charspace;
    }
}


function noisecolor()
// Fonction permettant de d�terminer la couleur du bruit et la forme du pinceau
{
    global $img, $noisecolorchar, $ink, $bg, $brushsize;
    switch ($noisecolorchar) {
        case 1  :
            $noisecol = $ink;
            break;
        case 2  :
            $noisecol = $bg;
            break;
        case 3  :
        default :
            $noisecol = imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255));
            break;
    }
    if ($brushsize and $brushsize > 1 and function_exists('imagesetbrush')) {
        $brush = imagecreatetruecolor($brushsize, $brushsize);
        imagefill($brush, 0, 0, $noisecol);
        imagesetbrush($img, $brush);
        $noisecol = IMG_COLOR_BRUSHED;
    }
    return $noisecol;
}


function bruit()
// Ajout de bruits: point, lignes et cercles al�atoires
{
    global $noisepxmin, $noisepxmax, $noiselinemin, $noiselinemax, $nbcirclemin, $nbcirclemax, $img, $cryptwidth, $cryptheight;
    $nbpx = rand($noisepxmin, $noisepxmax);
    $nbline = rand($noiselinemin, $noiselinemax);
    $nbcircle = rand($nbcirclemin, $nbcirclemax);
    for ($i = 1; $i < $nbpx; $i++) imagesetpixel($img, rand(0, $cryptwidth - 1), rand(0, $cryptheight - 1), noisecolor());
    for ($i = 1; $i <= $nbline; $i++) imageline($img, rand(0, $cryptwidth - 1), rand(0, $cryptheight - 1), rand(0, $cryptwidth - 1), rand(0, $cryptheight - 1), noisecolor());
    for ($i = 1; $i <= $nbcircle; $i++) imagearc($img, rand(0, $cryptwidth - 1), rand(0, $cryptheight - 1), $rayon = rand(5, $cryptwidth / 3), $rayon, 0, 360, noisecolor());
}


if ($noiseup) {
    ecriture();
    bruit();
} else {
    bruit();
    ecriture();
}


// Cr�ation du cadre
if ($bgframe) {
    $framecol = imagecolorallocate($img, ($bgR * 3 + $charR) / 4, ($bgG * 3 + $charG) / 4, ($bgB * 3 + $charB) / 4);
    imagerectangle($img, 0, 0, $cryptwidth - 1, $cryptheight - 1, $framecol);
}


// Transformations suppl�mentaires: Grayscale et Brouillage
// V�rifie si la fonction existe dans la version PHP install�e
if (function_exists('imagefilter')) {
    if ($cryptgrayscal) imagefilter($img, IMG_FILTER_GRAYSCALE);
    if ($cryptgaussianblur) imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
}


// Conversion du cryptogramme en Majuscule si insensibilit� � la casse
$word = ($difuplow ? $word : strtoupper($word));


// Retourne 2 informations dans la session: 
// - Le code du cryptogramme (crypt� ou pas)
// - La Date/Heure de la cr�ation du cryptogramme au format integer "TimeStamp" 
switch (strtoupper($cryptsecure)) {
    case "MD5"  :
        $_SESSION['cryptcode'] = md5($word);
        break;
    case "SHA1" :
        $_SESSION['cryptcode'] = sha1($word);
        break;
    default     :
        $_SESSION['cryptcode'] = $word;
        break;
}
$_SESSION['crypttime'] = time();
$_SESSION['cryptcptuse']++;

// Envoi de l'image finale au navigateur 
switch (strtoupper($cryptformat)) {
    case "JPG"  :
    case "JPEG" :
        if (imagetypes() & IMG_JPG) {
            header("Content-type: image/jpeg");
            imagejpeg($img, "", 80);
        }
        break;
    case "GIF"  :
        if (imagetypes() & IMG_GIF) {
            header("Content-type: image/gif");
            imagegif($img);
        }
        break;
    case "PNG"  :
    default     :
        if (imagetypes() & IMG_PNG) {
            header("Content-type: image/png");
            imagepng($img);
        }
}

imagedestroy($img);
unset ($word, $tword);
unset ($_SESSION['cryptreload']); 