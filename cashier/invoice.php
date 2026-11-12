<?php
require_once('../tcpdf/config/lang/eng.php');
require_once('../tcpdf/tcpdf.php');
include_once('../connect_db.php');
session_start();

if (!isset($_SESSION['custId'])) {
    header("Location: prescription.php");
    exit();
}

$c_id      = $_SESSION['custId'];
$cname     = $_SESSION['custName'];
$age       = $_SESSION['age'];
$sex       = $_SESSION['sex'];
$postal    = $_SESSION['postal_address'];
$phone     = $_SESSION['phone'];
$user      = $_SESSION['username'];
$invoiceNo = $_SESSION['invoice'];
$time      = date("l, F d Y, h:i:s A");

try {
    // 1. Get Next Prescription ID
    $stmt = $con->query("SELECT 1+MAX(prescription_id) FROM prescription");
    $presId = $stmt->fetchColumn();
    $presIdd = ($presId === false || $presId === null) ? 999 : $presId;

    // 2. Insert Prescription
    $stmt = $con->prepare("INSERT INTO prescription(prescription_id, customer_id, customer_name, age, sex, postal_address, invoice_id, phone) VALUES(:pid, :cid, :cname, :age, :sex, :postal, :inv, :phone)");
    $stmt->execute([
        ':pid' => $presIdd, ':cid' => $c_id, ':cname' => $cname,
        ':age' => $age, ':sex' => $sex, ':postal' => $postal,
        ':inv' => $invoiceNo, ':phone' => $phone
    ]);

    // 3. Insert Invoice
    $stmt = $con->prepare("INSERT INTO invoice(invoice_id, customer_name, served_by, status) VALUES(:inv, :cname, :user, 'Pending')");
    $stmt->execute([':inv' => $invoiceNo, ':cname' => $cname, ':user' => $user]);

    // 4. Fetch Temp Prescription Items
    $stmt = $con->prepare("SELECT * FROM tempprescri WHERE customer_id = :cid");
    $stmt->execute([':cid' => $c_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Prepare Data for TCPDF and calculate total
    $pdfData = [];
    $total = 0;
    
    // Add headers for the table
    $pdfData[] = ['Drug', 'Strength', 'Dose', 'Quantity', 'Price', 'Total'];

    foreach ($items as $item) {
        $stmtStock = $con->prepare("SELECT stock_id, drug_name, cost FROM stock WHERE drug_name = :dname");
        $stmtStock->execute([':dname' => $item['drug_name']]);
        $stockDetails = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if ($stockDetails) {
            $lineTotal = $stockDetails['cost'] * $item['quantity'];
            $total += $lineTotal;

            // Insert into invoice_details
            $stmtInv = $con->prepare("INSERT INTO invoice_details(invoice, drug, cost, quantity) VALUES(:inv, :drug, :cost, :qty)");
            $stmtInv->execute([
                ':inv' => $invoiceNo, 
                ':drug' => $stockDetails['stock_id'], 
                ':cost' => $stockDetails['cost'], 
                ':qty' => $item['quantity']
            ]);

            // Insert into prescription_details
            $stmtPres = $con->prepare("INSERT INTO prescription_details(pres_id, drug_name, strength, dose, quantity) VALUES(:pid, :dname, :str, :dose, :qty)");
            $stmtPres->execute([
                ':pid' => $presIdd, 
                ':dname' => $stockDetails['stock_id'], 
                ':str' => $item['strength'], 
                ':dose' => $item['dose'], 
                ':qty' => $item['quantity']
            ]);

            // Add to PDF Array
            $pdfData[] = [
                $stockDetails['drug_name'], 
                $item['strength'], 
                $item['dose'], 
                $item['quantity'], 
                number_format($stockDetails['cost'], 2), 
                number_format($lineTotal, 2)
            ];
        }
    }

    // Add Total Row
    $pdfData[] = ['', '', '', '', 'TOTAL', number_format($total, 2)];

    // 6. Clear Temp Table
    $stmtDel = $con->prepare("DELETE FROM tempprescri WHERE customer_id = :cid");
    $stmtDel->execute([':cid' => $c_id]);

} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// --- TCPDF GENERATION ---
class MYPDF extends TCPDF {
    public function ColoredTable($header, $data) {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0);
        $this->SetFont('', 'B');
        
        $w = array(40, 20, 15, 20, 25, 25); // Adjusted column widths for A4
        $num_headers = count($header);
        
        for($i = 0; $i < $num_headers; ++$i) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'L', 1);
        }
        $this->Ln();
        
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        
        $fill = 0;
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, $row[2], 'LR', 0, 'L', $fill);
            $this->Cell($w[3], 6, $row[3], 'LR', 0, 'R', $fill);
            $this->Cell($w[4], 6, $row[4], 'LR', 0, 'R', $fill);
            $this->Cell($w[5], 6, $row[5], 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Pharm Syst');
$pdf->SetTitle('Invoice');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetFont('times', '', 10, '', true);
$pdf->AddPage();

$spacing = -0.01;
$stretching = 75;
$pdf->setFontStretching($stretching);
$pdf->setFontSpacing($spacing);

$titling = <<<EOD
<strong> <font style="font-size:11">Pharmacy Sys</font> </strong> <br>
Student Center Ground Floor,<br> P.O. Box Private Bag Kabarak, Kenya <br> Tel: +254 702 937 925 <br> E-mail: pharmacysys@yahoo.com <br>-----------------------------------------
EOD;

$ddt = <<<EOD
<strong>INVOICE N<sup>o</sup>:</strong> $invoiceNo  <br>
$time   
EOD;

$html = <<<EOD
<strong>Name: </strong> $cname <strong>ID N<sup>o</sup>: $c_id
<br>-----------------------------------------
EOD;

$pdf->writeHTMLCell(0, 0, '', '', $titling, 0, 1, 0, true, 'C', true);
$pdf->writeHTMLCell(0, 0, '', '', $ddt, 0, 1, 0, true, 'L', true);
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

$header = array('Drug', 'Strength', 'Dose', 'Quantity', 'Price', 'Total');
$pdf->ColoredTable($header, $pdfData);

$pdf->SetY(-15);
$pdf->Cell(0, 0, 'You were served by: ' . strtoupper($user), 0, false, 'L', 0, '', 0, false, 'T', 'M');

// Ensure directory exists (create if not)
if (!is_dir('../receipts/printouts')) {
    mkdir('../receipts/printouts', 0777, true);
}

// Save the PDF
$pdf->Output('../receipts/printouts/'.$cname.'-invoice.pdf', 'F');

// Cleanup session
unset($_SESSION['custId'], $_SESSION['custName'], $_SESSION['age'], $_SESSION['sex'], $_SESSION['postal_address'], $_SESSION['phone']);

// Redirect back
header('Location: prescription.php');
exit;
?>