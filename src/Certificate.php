<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/Database.php';

use Fpdf\Fpdf;

class Certificate {
  public static function generate($clearanceId, $userId): string {
    // Fetch clearance + user
    $stmt = Database::$pdo->prepare('SELECT c.*, u.name, u.reg_no, u.email FROM clearances c JOIN users u ON u.id=c.user_id WHERE c.id=?');
    $stmt->execute([$clearanceId]);
    $cl = $stmt->fetch();
    if (!$cl) throw new Exception('Clearance not found');

    // Generate verification code
    $code = bin2hex(random_bytes(8));
    $path = __DIR__.'/../public/certificates/'.$code.'.pdf';

    // Ensure dir exists
    if (!is_dir(dirname($path))) mkdir(dirname($path),0775,true);

    // Build PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Ardhi University — Clearance Certificate',0,1,'C');
    $pdf->Ln(10);
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,8,
      "This certifies that {$cl['name']} (Reg No: {$cl['reg_no']}) has been cleared for graduation (Level: {$cl['level']}, Program: {$cl['program']}, Completion Year: {$cl['completion_year']})."
    );

    // QR Code (simple text-based fallback)
    $url = ($_ENV['APP_URL'] ?? 'http://localhost/CGS/public')."/verify.php?code=$code";
    $qrFile = sys_get_temp_dir()."/$code.png";
    // generate QR via Google Chart API (simplest, no lib dependency)
    file_put_contents($qrFile, file_get_contents("https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=".urlencode($url)));
    $pdf->Image($qrFile, $pdf->GetX()+60, $pdf->GetY()+20, 50, 50);

    $pdf->Ln(80);
    $pdf->Cell(0,10,'Issued at: '.date('Y-m-d H:i:s'),0,1);

    $pdf->Output('F',$path);

    // Save record
    $stmt2=Database::$pdo->prepare('INSERT INTO certificates(clearance_id,file_path,verification_code) VALUES(?,?,?)');
    $stmt2->execute([$clearanceId,$path,$code]);

    return $path;
  }
}
?>
