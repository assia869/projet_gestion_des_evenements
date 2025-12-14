<?php
// /gestion-evenements/services/TicketService.php  (REMPLACE TOUT LE FICHIER)
// ✅ Compatible endroid/qr-code v6.0.9 (QrCode en constructeur + writer->write())

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    echo "Dépendances Composer manquantes.\n\n";
    echo "Exécute :\n";
    echo "  cd C:\\xampp\\htdocs\\gestion-evenements\n";
    echo "  composer install\n\n";
    exit;
}
require_once $autoload;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class TicketService
{
    private string $ticketsDirFs;
    private string $ticketsDirWeb;

    public function __construct()
    {
        $this->ticketsDirFs  = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/gestion-evenements/assets/tickets/';
        $this->ticketsDirWeb = '/gestion-evenements/assets/tickets/';

        if (!is_dir($this->ticketsDirFs)) {
            mkdir($this->ticketsDirFs, 0777, true);
        }
    }

    private function enc(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
        return $out !== false ? $out : $s;
    }

    public function buildAndSavePdf(array $ticketData): array
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException("Extension PHP 'gd' manquante. Active-la dans C:\\xampp\\php\\php.ini (extension=gd) puis redémarre Apache.");
        }

        $token = (string)($ticketData['token'] ?? '');
        if ($token === '') {
            throw new RuntimeException('Token manquant pour la génération du ticket.');
        }

        $pdfFilename = 'invitation_' . $token . '.pdf';
        $qrFilename  = 'qr_' . $token . '.png';

        $pdfFs = $this->ticketsDirFs . $pdfFilename;
        $qrFs  = $this->ticketsDirFs . $qrFilename;

        $qrData = (string)($ticketData['verify_url'] ?? '');
        if ($qrData === '') {
            throw new RuntimeException('URL de vérification manquante.');
        }

        // ✅ QR (endroid/qr-code v6.0.x)
        $qrCode = new QrCode(
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $result->saveToFile($qrFs);

        // ✅ PDF (FPDF)
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->SetLineWidth(0.4);
        $pdf->Rect(10, 10, 190, 277);

        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetXY(10, 18);
        $pdf->Cell(190, 10, $this->enc('Invitation / Billet'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetX(10);
        $pdf->Cell(190, 6, $this->enc('Présentez ce billet à l’entrée. QR Code unique inclus.'), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->Line(15, 40, 195, 40);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetXY(15, 46);
        $pdf->Cell(0, 8, $this->enc('Détails de l’événement'), 0, 1);

        $pdf->SetFont('Arial', '', 11);

        $eventTitle   = (string)($ticketData['event_title'] ?? '');
        $eventDate    = (string)($ticketData['event_date'] ?? '');
        $eventStart   = (string)($ticketData['event_start'] ?? '');
        $eventEnd     = (string)($ticketData['event_end'] ?? '');
        $eventLoc     = (string)($ticketData['event_location'] ?? '');
        $contactEmail = (string)($ticketData['event_contact_email'] ?? '');
        $contactPhone = (string)($ticketData['event_contact_phone'] ?? '');

        $pdf->SetX(15);
        $pdf->MultiCell(120, 6, $this->enc("Événement : {$eventTitle}"));

        $pdf->SetX(15);
        $pdf->Cell(120, 6, $this->enc("Date : {$eventDate}"), 0, 1);

        $pdf->SetX(15);
        $pdf->Cell(120, 6, $this->enc("Horaire : {$eventStart} - {$eventEnd}"), 0, 1);

        $pdf->SetX(15);
        $pdf->MultiCell(120, 6, $this->enc("Lieu : {$eventLoc}"));

        $pdf->Ln(2);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, $this->enc('Contact'), 0, 1);

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetX(15);
        $pdf->Cell(120, 6, $this->enc("Email : " . ($contactEmail !== '' ? $contactEmail : '—')), 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(120, 6, $this->enc("Téléphone : " . ($contactPhone !== '' ? $contactPhone : '—')), 0, 1);

        // QR à droite
        $pdf->Image($qrFs, 145, 52, 50, 50);

        $pdf->Ln(6);
        $pdf->Line(15, 132, 195, 132);

        $pdf->SetXY(15, 138);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, $this->enc('Participant'), 0, 1);

        $pdf->SetFont('Arial', '', 11);
        $pName  = (string)($ticketData['participant_name'] ?? '');
        $pEmail = (string)($ticketData['participant_email'] ?? '');
        $pPhone = (string)($ticketData['participant_phone'] ?? '');

        $pdf->SetX(15);
        $pdf->Cell(0, 6, $this->enc("Nom : {$pName}"), 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(0, 6, $this->enc("Email : {$pEmail}"), 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(0, 6, $this->enc("Téléphone : " . ($pPhone !== '' ? $pPhone : '—')), 0, 1);

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetX(15);
        $pdf->MultiCell(180, 5, $this->enc("Ticket ID : {$token}\nVérification : {$qrData}"));

        $pdf->SetY(-25);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 8, $this->enc('© Gestion Événements - Billet généré automatiquement'), 0, 0, 'C');

        $pdf->Output('F', $pdfFs);

        return [
            'pdf_fs'  => $pdfFs,
            'pdf_web' => $this->ticketsDirWeb . $pdfFilename,
            'qr_fs'   => $qrFs,
            'qr_web'  => $this->ticketsDirWeb . $qrFilename,
        ];
    }
}
