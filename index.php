<?php
/**
 * This is a sample authentication file for mabel using Raven.
 * 
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately..uB 
 * 
 * @category  CategoryName
 * @package   PackageName
 * @author    Original Author <author@example.com>
 * @author    Another Author <another@example.com>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/PackageName
 */

require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;

$STUDENT_GROUP = 2;
$ALUM_GROUP= 4;
$emma_insts = array("EMM","EMMPG","EMMUG");
$file = file('mabel.txt');
$MABEL_KEY=trim($file[0]);

// get the current user
if (isSet($_SERVER['REMOTE_USER'])) {
    $crsid = $_SERVER['REMOTE_USER'];
} else {
    // TODO: fail here - somehow avoided authentication
    die("Raven authentication failed :(");
}

$groups = array();

// first check IBIS if possible
require_once "ibisclient/client/IbisClientConnection.php";
require_once "ibisclient/methods/PersonMethods.php";

$uni_student = false;
$uni_alum = false;
$uni_staff = false;
$college_student = false;
$college_alum = false;
$college_staff = false;

$conn = IbisClientConnection::createConnection();
$pm = new PersonMethods($conn);
$person = $pm->getPerson('crsid', $crsid, 'email,all_insts,name');

if ($person->cancelled) {
    $uni_alum = true;
} 
// TODO: (if possible) check alumnus college affiliation

if ($person->misAffiliation=="student" || $person->misAffiliation=="student,staff" || $person->misAffiliation=="staff,student") {
    $uni_student = true;
}
if ($person->misAffiliation=="staff" || $person->misAffiliation=="student,staff" || $person->misAffiliation=="staff,student") {
    $uni_staff = true;
}

foreach ($person->institutions as $inst) {
    foreach ($emma_insts as $emma_inst) {
        if ($inst->instid == $emma_inst) {
            $college_student = $uni_student; 
            $college_staff = $uni_staff; 
            $college_alum = $uni_alum;
        }
    }
}

// select the right mabel groups now we know about the user

$groups = array();
// if ($college_staff) array_push($groups, "college_staff");
if ($college_student) array_push($groups, 2);
if ($college_alum) array_push($groups, 3);
// if ($uni_staff) array_push($groups, "uni_staff");
// if ($uni_student) array_push($groups, "uni_student");
// if ($uni_alum) array_push($groups, "uni_alum");

$data = array(
        'groups'   => $groups, // userid from the users table
        'crsid' => $crsid, // User name
        'name' =>$person->visibleName,
        'is_verified' => true,
        'email' => $crsid . "@cam.ac.uk" // email
);
$json = json_encode($data);
$token = JWT::encode($data, $MABEL_KEY, 'HS256');

$url = $_GET["redirect_to"] . "/". $token;
header("Location: " . $url);
die();
?>
