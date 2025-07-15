<?php
// api/customer/download.php - Generates PDF for customer invoices and quotes

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Required for FPDF

if (!is_logged_in() || !has_role('customer')) {
    die('Unauthorized access.');
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (empty($type) || !$id) {
    die('Invalid request.');
}

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo
        $this->Image(__DIR__.'/../../assets/images/logo.png',10,6,30);
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30,10,ucfirst($_GET['type']),1,0,'C');
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Create new PDF instance
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

if ($type === 'invoice') {
    // Fetch invoice data, ensuring it belongs to the logged-in user
    $stmt = $conn->prepare("SELECT i.*, u.first_name, u.last_name FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ? AND i.user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if($invoice) {
        $pdf->Cell(0,10,'Invoice #: '.$invoice['invoice_number'],0,1);
        $pdf->Cell(0,10,'Customer: '.$invoice['first_name'].' '.$invoice['last_name'],0,1);
        $pdf->Cell(0,10,'Date: '.$invoice['created_at'],0,1);
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(120,10,'Description',1);
        $pdf->Cell(20,10,'Qty',1);
        $pdf->Cell(25,10,'Unit Price',1);
        $pdf->Cell(25,10,'Total',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);

        // Fetch invoice items
        $stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $items = $stmt_items->get_result();
        while($item = $items->fetch_assoc()){
            $pdf->Cell(120,10,$item['description'],1);
            $pdf->Cell(20,10,$item['quantity'],1);
            $pdf->Cell(25,10,'$'.number_format($item['unit_price'],2),1);
            $pdf->Cell(25,10,'$'.number_format($item['total'],2),1);
            $pdf->Ln();
        }
        $stmt_items->close();

        $pdf->Ln(10);
        $pdf->Cell(145,10,'Subtotal',0,0,'R');
        $pdf->Cell(45,10,'$'.number_format($invoice['amount'] + $invoice['discount'] - $invoice['tax'],2),1,'R');
        $pdf->Cell(145,10,'Discount',0,0,'R');
        $pdf->Cell(45,10,'-$'.number_format($invoice['discount'],2),1,'R');
        $pdf->Cell(145,10,'Tax',0,0,'R');
        $pdf->Cell(45,10,'$'.number_format($invoice['tax'],2),1,'R');
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(145,10,'Grand Total',0,0,'R');
        $pdf->Cell(45,10,'$'.number_format($invoice['amount'],2),1,'R');

        $pdf->Output('D', 'Invoice-'.$invoice['invoice_number'].'.pdf');
    }

} elseif ($type === 'quote') {
    // Fetch quote data, ensuring it belongs to the logged-in user
    $stmt = $conn->prepare("SELECT q.*, u.first_name, u.last_name FROM quotes q JOIN users u ON q.user_id = u.id WHERE q.id = ? AND q.user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $quote = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($quote){
        $pdf->Cell(0,10,'Quote #: Q'.$quote['id'],0,1);
        $pdf->Cell(0,10,'Customer: '.$quote['first_name'].' '.$quote['last_name'],0,1);
        $pdf->Cell(0,10,'Date: '.$quote['created_at'],0,1);
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Details',0,1);
        $pdf->SetFont('Arial','',12);

        $details = json_decode($quote['quote_details'], true);
        if($details && is_array($details)){
            foreach($details as $key => $value){
                $pdf->Cell(0,10,ucwords(str_replace('_',' ',$key)).': '. (is_array($value) ? json_encode($value) : $value) ,0,1);
            }
        }
        
        $pdf->Ln(10);
        $pdf->Cell(145,10,'Quoted Price',0,0,'R');
        $pdf->Cell(45,10,'$'.number_format($quote['quoted_price'],2),1,'R');
        $pdf->Cell(145,10,'Discount',0,0,'R');
        $pdf->Cell(45,10,'-$'.number_format($quote['discount'],2),1,'R');
        $pdf->Cell(145,10,'Tax',0,0,'R');
        $pdf->Cell(45,10,'$'.number_format($quote['tax'],2),1,'R');
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(145,10,'Total',0,0,'R');
        $pdf->Cell(45,10,'$'.number_format($quote['quoted_price'] - $quote['discount'] + $quote['tax'],2),1,'R');

        if($quote['attachment_path']){
            $pdf->Ln(10);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,10,'Attachment Included',0,1);
        }

        $pdf->Output('D', 'Quote-Q'.$quote['id'].'.pdf');
    }
}

$conn->close();
?>