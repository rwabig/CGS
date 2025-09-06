<?php require_once __DIR__.'/../../src/bootstrap.php'; Auth::requireRole(['admin']);
require_once __DIR__.'/../../src/Certificate.php';

$cid=(int)($_GET['cid']??0);
if($cid){
  try{
    $file=Certificate::generate($cid,$_SESSION['uid']);
    echo "<p>Certificate generated: <a href='../certificates/".basename($file)."'>Download PDF</a></p>";
  }catch(Exception $e){ echo "<p>Error: ".$e->getMessage()."</p>"; }
} else {
  echo "<p>No clearance ID given</p>";
}
?>
