<?php
// FILE: classes/TicketService.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/mail.php';

class TicketService
{
    private string $vendorAutoload;

    public function __construct()
    {
        $this->vendorAutoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($this->vendorAutoload)) {
            throw new RuntimeException("vendor/autoload.php introuvable. Installe les dépendances Composer.");
        }
        require_once $this->vendorAutoload;

        $this->ensureStorageDirs();
    }

    private function ensureStorageDirs(): void
    {
        if (!is_dir(APP_STORAGE_DIR)) {
            mkdir(APP_STORAGE_DIR, 0777, true);
        }
        if (!is_dir(APP_TICKETS_DIR)) {
            mkdir(APP_TICKETS_DIR, 0777, true);
        }
        if (!is_dir(APP_QRCODES_DIR)) {
            mkdir(APP_QRCODES_DIR, 0777, true);
        }
    }

    public function getOrCreateQrPath(string $token, string $qrDataUrl): string
    {
        $qrPath = APP_QRCODES_DIR . '/' . $token . '.png';
        if (file_exists($qrPath)) {
            return $qrPath;
        }

        $this->generateQrPng($qrDataUrl, $qrPath);
        return $qrPath;
    }

    public function getOrCreatePdfPath(array $ticketData): string
    {
        if (empty($ticketData['ticket_token'])) {
            throw new InvalidArgumentException('ticket_token manquant.');
        }

        $token = (string) $ticketData['ticket_token'];
        $pdfPath = APP_TICKETS_DIR . '/' . $token . '.pdf';
        if (file_exists($pdfPath)) {
            return $pdfPath;
        }

        $verifyUrl = APP_URL . '/verify_ticket.php?token=' . urlencode($token);
        $qrPath = $this->getOrCreateQrPath($token, $verifyUrl);

        $this->generatePdf($ticketData, $qrPath, $pdfPath);
        return $pdfPath;
    }

    private function pdfText(string $text): string
    {
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        return $converted !== false ? $converted : utf8_decode($text);
    }

    private function formatDateFr(?string $date): string
    {
        if (!$date) return '—';
        try {
            $dt = new DateTime($date);
            return $dt->format('d/m/Y');
        } catch (Throwable) {
            return $date;
        }
    }

    private function formatTime(?string $time): string
    {
        if (!$time) return '—';
        return substr($time, 0, 5);
    }

    private function generatePdf(array $ticketData, string $qrPngPath, string $outputPdfPath): void
    {
        // FPDF
        $pdf = new \Fpdf\Fpdf('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 10, $this->pdfText('Invitation / Billet'), 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 7, $this->pdfText(APP_NAME), 0, 1, 'C');
        $pdf->Ln(6);

        // Ticket block
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, $this->pdfText('Informations Participant'), 0, 1);
        $pdf->SetFont('Arial', '', 12);

        $pdf->Cell(45, 7, $this->pdfText('Nom :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText((string)($ticketData['nom_participant'] ?? '—')), 0, 1);

        $pdf->Cell(45, 7, $this->pdfText('Email :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText((string)($ticketData['email_participant'] ?? '—')), 0, 1);

        $pdf->Cell(45, 7, $this->pdfText('Téléphone :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText((string)($ticketData['telephone'] ?? '—')), 0, 1);

        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, $this->pdfText('Informations Événement'), 0, 1);
        $pdf->SetFont('Arial', '', 12);

        $pdf->Cell(45, 7, $this->pdfText('Événement :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText((string)($ticketData['titre'] ?? '—')), 0, 1);

        $pdf->Cell(45, 7, $this->pdfText('Date :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText($this->formatDateFr($ticketData['date_evenement'] ?? null)), 0, 1);

        $pdf->Cell(45, 7, $this->pdfText('Horaire :'), 0, 0);
        $horaire = $this->formatTime($ticketData['heure_debut'] ?? null) . ' - ' . $this->formatTime($ticketData['heure_fin'] ?? null);
        $pdf->Cell(0, 7, $this->pdfText($horaire), 0, 1);

        $pdf->Cell(45, 7, $this->pdfText('Lieu :'), 0, 0);
        $pdf->MultiCell(0, 7, $this->pdfText((string)($ticketData['lieu'] ?? '—')), 0);

        $pdf->Ln(4);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, $this->pdfText('Contact'), 0, 1);
        $pdf->SetFont('Arial', '', 12);

        $pdf->Cell(45, 7, $this->pdfText('Email :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText(APP_CONTACT_EMAIL), 0, 1);

        $pdf->Cell(45, 7, $this->pdfText('Téléphone :'), 0, 0);
        $pdf->Cell(0, 7, $this->pdfText(APP_CONTACT_PHONE), 0, 1);

        // QR block (right side)
        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, $this->pdfText('QR Code (Vérification)'), 0, 1);

        // Place QR
        $x = 150;
        $y = $pdf->GetY();
        $pdf->Image($qrPngPath, $x, $y, 45, 45);

        // Token display
        $pdf->SetY($y);
        $pdf->SetX(10);
        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(130, 6, $this->pdfText("Code billet : " . (string)($ticketData['ticket_token'] ?? '—')), 0);

        $pdf->Ln(40);

        $pdf->SetFont('Arial', 'I', 10);
        $pdf->MultiCell(0, 5, $this->pdfText("Présente ce PDF à l’entrée. Le QR code permet de vérifier la validité de l’inscription."), 0);

        $pdf->Output('F', $outputPdfPath);
    }

    private function generateQrPng(string $data, string $outputPath): void
    {
        $result = \Endroid\QrCode\Builder\Builder::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->data($data)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
            ->size(320)
            ->margin(10)
            ->build();

        $result->saveToFile($outputPath);
    }

    public function sendTicketByEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $pdfPath): bool
    {
        if (!MAIL_ENABLED) {
            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->Port = MAIL_PORT;

            if (MAIL_ENCRYPTION === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->addAttachment($pdfPath);

            return $mail->send();
        } catch (\Throwable) {
            return false;
        }
    }
}
