<?php
use FPDF\FPDF;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

class Certificate {
    public static function generate($userId, $clearanceId) {
        // Fetch student profile
        $stmt = Database::$pdo->prepare("
            SELECT sp.*, u.name AS user_name, u.email,
                   o.name AS organization_name, d.name AS department_name,
                   p.name AS program_name, c.name AS category_name
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.id
            LEFT JOIN organizations o ON sp.organization_id = o.id
            LEFT JOIN departments d ON sp.department_id = d.id
            LEFT JOIN programs p ON sp.program_id = p.id
            LEFT JOIN categories c ON sp.category_id = c.id
            WHERE sp.user_id = ?
        ");
        $stmt->execute([$userId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new Exception("Student profile not found.");
        }

        // Fetch signatories
        $stmt = Database::$pdo->prepare("
            SELECT s.order_no, u.name AS signatory_name, st.name AS title, cs.comments, cs.signed_at
            FROM clearance_signatures cs
            JOIN users u ON cs.signatory_id = u.id
            LEFT JOIN staff_profiles sp ON sp.user_id = u.id
            LEFT JOIN staff_titles st ON sp.staff_title_id = st.id
            JOIN signatory_steps s ON cs.step_id = s.id
            WHERE cs.clearance_id = ?
            ORDER BY s.order_no ASC
        ");
        $stmt->execute([$clearanceId]);
        $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // QR code
        $verifyUrl = "http://localhost/CGS/public/verify.php?code=" . md5($clearanceId . $userId);
        $qr = QrCode::create($verifyUrl)->setSize(120);
        $writer = new PngWriter();
        $qrResult = $writer->write($qr);

        $qrPath = sys_get_temp_dir() . "/qr_" . uniqid() . ".png";
        $qrResult->saveToFile($qrPath);

        // PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,'Clearance Certificate',0,1,'C');

        // Student photo
        if (!empty($student['photo_path']) && file_exists(__DIR__ . '/../public/' . $student['photo_path'])) {
            $pdf->Image(__DIR__ . '/../public/' . $student['photo_path'], 160, 20, 30, 30);
        }

        $pdf->Ln(20);
        $pdf->SetFont('Arial','',12);
        $pdf->MultiCell(0,8,
            "This is to certify that the student has successfully completed clearance.\n\n" .
            "Name: " . $student['candidate_name'] . "\n" .
            "Reg No: " . $student['reg_no'] . "\n" .
            "Organization: " . $student['organization_name'] . "\n" .
            "Department: " . $student['department_name'] . "\n" .
            "Program: " . $student['program_name'] . "\n" .
            "Category: " . $student['category_name'] . "\n" .
            "Completion Year: " . $student['completion_year'] . "\n" .
            "Date of Graduation: " . $student['graduation_date']
        );

        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Signatories',0,1,'L');

        // Signatories table
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(15,8,'#',1);
        $pdf->Cell(40,8,'Name',1);
        $pdf->Cell(40,8,'Title',1);
        $pdf->Cell(65,8,'Comments',1);
        $pdf->Cell(30,8,'Date',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',9);
        foreach ($signatories as $sig) {
            $pdf->Cell(15,8,$sig['order_no'],1);
            $pdf->Cell(40,8,utf8_decode($sig['signatory_name']),1);
            $pdf->Cell(40,8,utf8_decode($sig['title']),1);
            $x=$pdf->GetX(); $y=$pdf->GetY();
            $pdf->MultiCell(65,8,utf8_decode($sig['comments']),1);
            $pdf->SetXY($x+65,$y);
            $pdf->Cell(30,8,$sig['signed_at'],1);
            $pdf->Ln();
        }

        // QR code with clickable link
        $pdf->Ln(10);
        $pdf->Image($qrPath, 10, $pdf->GetY(), 30, 30, 'PNG');
        $pdf->SetXY(45, $pdf->GetY()+10);
        $pdf->SetFont('Arial','U',10);
        $pdf->SetTextColor(0,0,255);
        $pdf->Cell(0,10,$verifyUrl,0,1,'L',false,$verifyUrl);

        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('Arial','I',9);
        $pdf->SetTextColor(0,0,0);
        $pdf->MultiCell(0,6,
            "Issued on: " . date("d M Y H:i") . "\n" .
            "Certificate issued through Clearance for Graduating Students (CGS)"
        );

        // Cleanup
        if (file_exists($qrPath)) unlink($qrPath);

        $pdf->Output("I", "certificate.pdf");
    }
}
?>
