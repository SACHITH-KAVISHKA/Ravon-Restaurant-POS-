<?php

namespace App\Services;

use App\Models\KitchenStation;
use Exception;

class PrinterService
{
    /**
     * Print KOT to kitchen printer.
     */
    public function printKOT(KitchenStation $station, array $kotData): bool
    {
        try {
            if (!$station->hasPrinter()) {
                throw new Exception('Printer not configured for station: ' . $station->name);
            }

            // Format KOT content
            $content = $this->formatKOTContent($kotData);

            // Send to printer via QZ Tray or direct printing
            return $this->sendToPrinter($station->printer_name, $content, 'kot');

        } catch (Exception $e) {
            logger()->error('KOT Print Error', [
                'station' => $station->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Print bill/receipt.
     */
    public function printBill(array $billData): bool
    {
        try {
            $content = $this->formatBillContent($billData);
            
            // Get receipt printer name from config
            $printerName = config('printer.receipt_printer');

            return $this->sendToPrinter($printerName, $content, 'bill');

        } catch (Exception $e) {
            logger()->error('Bill Print Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Format KOT content for thermal printer.
     */
    protected function formatKOTContent(array $data): string
    {
        $content = "";
        
        // Header
        $content .= $this->centerText("*** KITCHEN ORDER TICKET ***") . "\n";
        $content .= $this->drawLine() . "\n";
        $content .= "KOT#: " . $data['kot_number'] . "\n";
        $content .= "Order#: " . $data['order_number'] . "\n";
        $content .= "Table: " . $data['table'] . ($data['floor'] ? " ({$data['floor']})" : "") . "\n";
        $content .= "Type: " . $data['order_type'] . "\n";
        $content .= "Waiter: " . $data['waiter'] . "\n";
        $content .= "Time: " . $data['time'] . " | Date: " . $data['date'] . "\n";
        $content .= $this->drawLine() . "\n\n";

        // Items
        $content .= $this->bold("ITEMS:") . "\n";
        $content .= $this->drawLine() . "\n";
        
        foreach ($data['items'] as $item) {
            $content .= sprintf("%-4s %s\n", "x{$item['quantity']}", strtoupper($item['name']));
            
            // Modifiers
            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modifier) {
                    $content .= "     + " . $modifier['name'] . "\n";
                }
            }
            
            // Special instructions
            if (!empty($item['instructions'])) {
                $content .= "     NOTE: " . strtoupper($item['instructions']) . "\n";
            }
            
            $content .= "\n";
        }

        // Special instructions
        if (!empty($data['special_instructions'])) {
            $content .= $this->drawLine() . "\n";
            $content .= $this->bold("SPECIAL INSTRUCTIONS:") . "\n";
            $content .= strtoupper($data['special_instructions']) . "\n";
        }

        $content .= "\n" . $this->drawLine() . "\n";
        $content .= $this->centerText("*** END OF KOT ***") . "\n";
        
        // Cut paper
        $content .= $this->cutPaper();

        return $content;
    }

    /**
     * Format bill content for thermal printer.
     */
    protected function formatBillContent(array $data): string
    {
        $content = "";
        
        // Restaurant Header
        $content .= $this->centerText($this->bold($data['restaurant_name'])) . "\n";
        if (!empty($data['restaurant_address'])) {
            $content .= $this->centerText($data['restaurant_address']) . "\n";
        }
        if (!empty($data['restaurant_phone'])) {
            $content .= $this->centerText("Tel: " . $data['restaurant_phone']) . "\n";
        }
        $content .= $this->drawLine() . "\n";

        // Order Info
        $content .= "Bill#: " . $data['order_number'] . "\n";
        $content .= "Date: " . $data['order_date'] . "\n";
        $content .= "Type: " . $data['order_type'] . "\n";
        if ($data['table'] !== 'N/A') {
            $content .= "Table: " . $data['table'] . "\n";
        }
        $content .= "Guests: " . $data['guest_count'] . "\n";
        $content .= "Waiter: " . $data['waiter'] . "\n";
        $content .= $this->drawLine() . "\n";

        // Items
        $content .= sprintf("%-20s %3s %8s %10s\n", "Item", "Qty", "Price", "Amount");
        $content .= $this->drawLine() . "\n";

        foreach ($data['items'] as $item) {
            $content .= sprintf("%-20s %3d %8.2f %10.2f\n", 
                substr($item['name'], 0, 20),
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal']
            );
            
            // Modifiers
            foreach ($item['modifiers'] as $mod) {
                if ($mod['price'] != 0) {
                    $content .= sprintf("  + %-17s %3s %8.2f\n", 
                        substr($mod['name'], 0, 17), '', $mod['price']
                    );
                }
            }
        }

        $content .= $this->drawLine() . "\n";

        // Totals
        $content .= sprintf("%-30s %10.2f\n", "Subtotal:", $data['subtotal']);
        
        if ($data['discount'] > 0) {
            $content .= sprintf("%-30s -%9.2f\n", "Discount:", $data['discount']);
        }
        
        if ($data['service_charge'] > 0) {
            $content .= sprintf("%-30s %10.2f\n", "Service Charge (10%):", $data['service_charge']);
        }
        
        if ($data['tax'] > 0) {
            $content .= sprintf("%-30s %10.2f\n", "Tax (5%):", $data['tax']);
        }
        
        if ($data['delivery_fee'] > 0) {
            $content .= sprintf("%-30s %10.2f\n", "Delivery Fee:", $data['delivery_fee']);
        }

        $content .= $this->drawLine() . "\n";
        $content .= $this->bold(sprintf("%-30s %10.2f\n", "TOTAL:", $data['total']));
        $content .= $this->drawLine() . "\n";

        // Payment
        if (!empty($data['payment_method'])) {
            $method = strtoupper($data['payment_method']);
            $content .= sprintf("%-30s %s\n", "Payment Method:", $method);
            $content .= sprintf("%-30s %10.2f\n", "Paid:", $data['paid_amount']);
            if ($data['change'] > 0) {
                $content .= sprintf("%-30s %10.2f\n", "Change:", $data['change']);
            }
            $content .= $this->drawLine() . "\n";
        }

        // Footer
        $content .= "\n";
        $content .= $this->centerText($data['footer_message']) . "\n";
        $content .= $this->centerText("Printed: " . $data['printed_at']) . "\n";
        $content .= "\n";
        
        // QR Code for feedback (optional)
        // $content .= $this->generateQRCode($data['order_number']);
        
        $content .= $this->cutPaper();

        return $content;
    }

    /**
     * Send content to printer.
     * This is a placeholder - implement actual printer communication.
     */
    protected function sendToPrinter(string $printerName, string $content, string $type): bool
    {
        // Option 1: QZ Tray (JavaScript-based, recommended)
        // Return data to frontend for QZ Tray to handle
        
        // Option 2: Direct network printing
        // if (config('printer.method') === 'network') {
        //     return $this->printViaNetwork($printerName, $content);
        // }

        // Option 3: Windows printer
        // if (config('printer.method') === 'windows') {
        //     return $this->printViaWindows($printerName, $content);
        // }

        // For now, log the print job
        logger()->info('Print Job', [
            'printer' => $printerName,
            'type' => $type,
            'content_length' => strlen($content),
        ]);

        // Store in session for JavaScript to pick up
        session()->put("print_job_{$type}", [
            'printer' => $printerName,
            'content' => $content,
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Helper: Center text.
     */
    protected function centerText(string $text, int $width = 42): string
    {
        $padding = ($width - strlen($text)) / 2;
        return str_repeat(' ', max(0, $padding)) . $text;
    }

    /**
     * Helper: Draw line.
     */
    protected function drawLine(int $width = 42): string
    {
        return str_repeat('-', $width);
    }

    /**
     * Helper: Bold text (ESC/POS command).
     */
    protected function bold(string $text): string
    {
        return "\x1B\x45\x01" . $text . "\x1B\x45\x00";
    }

    /**
     * Helper: Cut paper (ESC/POS command).
     */
    protected function cutPaper(): string
    {
        return "\x1D\x56\x00"; // Full cut
    }
}
