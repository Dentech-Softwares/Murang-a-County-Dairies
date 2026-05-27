<?php
class ReportService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getDailySummary($date) {
        $stmt = $this->pdo->prepare("
            SELECT d.id AS dairy_id, d.name AS dairy_name,
            COALESCE(daily_coll.qty, 0) AS total_quantity,
            COALESCE(daily_coll.amt, 0) AS total_amount,
            (COALESCE(cum_coll.qty, 0) - COALESCE(cum_sales.qty, 0)) AS available_milk
            FROM dairies d
            LEFT JOIN (SELECT dairy_id, SUM(quantity) AS qty, SUM(total_price) AS amt FROM milk_collection WHERE DATE(date_collected) = ? GROUP BY dairy_id) AS daily_coll ON d.id = daily_coll.dairy_id
            LEFT JOIN (SELECT dairy_id, SUM(quantity) AS qty FROM milk_collection WHERE DATE(date_collected) <= ? GROUP BY dairy_id) AS cum_coll ON d.id = cum_coll.dairy_id
            LEFT JOIN (SELECT dairy_id, SUM(quantity) AS qty FROM milk_sales WHERE DATE(date_sold) <= ? GROUP BY dairy_id) AS cum_sales ON d.id = cum_sales.dairy_id
            ORDER BY d.name ASC");
        $stmt->execute([$date, $date, $date]);
        return $stmt->fetchAll();
    }

    public function getMonthlyStats($date) {
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));

        $coll = $this->pdo->prepare("SELECT SUM(quantity) as qty, SUM(total_price) as cost FROM milk_collection WHERE MONTH(date_collected) = ? AND YEAR(date_collected) = ?");
        $coll->execute([$month, $year]);
        $coll_data = $coll->fetch();

        $sales = $this->pdo->prepare("SELECT SUM(total_price) as rev, SUM(quantity) as qty FROM milk_sales WHERE MONTH(date_sold) = ? AND YEAR(date_sold) = ?");
        $sales->execute([$month, $year]);
        $sales_data = $sales->fetch();

        return [
            'volume' => $coll_data['qty'] ?: 0,
            'profit' => ($sales_data['rev'] ?: 0) - ($coll_data['cost'] ?: 0),
            'sales_qty' => $sales_data['qty'] ?: 0,
            'sales_rev' => $sales_data['rev'] ?: 0,
            'coll_cost' => $coll_data['cost'] ?: 0
        ];
    }

    public function getDailySales($date) {
        $stmt = $this->pdo->prepare("SELECT d.name as dairy_name, GROUP_CONCAT(DISTINCT ms.sold_to SEPARATOR ', ') as buyers, SUM(ms.quantity) as total_quantity, SUM(ms.total_price) as total_amount
                    FROM milk_sales ms JOIN dairies d ON ms.dairy_id = d.id 
                    WHERE CAST(ms.date_sold AS DATE) = ? GROUP BY d.id ORDER BY d.name ASC");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    public function getFarmerReport($date) {
        $stmt = $this->pdo->prepare("SELECT f.farmer_number, f.full_name, d.name as dairy_name, SUM(mc.quantity) as total_quantity, SUM(mc.total_price) as total_amount
                    FROM milk_collection mc JOIN farmers f ON mc.farmer_id = f.id JOIN dairies d ON f.dairy_id = d.id 
                    WHERE DATE(mc.date_collected) = ? GROUP BY f.id ORDER BY total_quantity DESC");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    public function getDailyPerformanceBreakdown($date) {
        $stmt = $this->pdo->prepare("SELECT d.name, 
                    COALESCE((SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = d.id AND DATE(date_collected) = ?), 0) as c_qty,
                    COALESCE((SELECT SUM(total_price) FROM milk_collection WHERE dairy_id = d.id AND DATE(date_collected) = ?), 0) as c_amt,
                    (SELECT GROUP_CONCAT(DISTINCT sold_to SEPARATOR ', ') FROM milk_sales WHERE dairy_id = d.id AND DATE(date_sold) = ?) as buyers,
                    COALESCE((SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = d.id AND DATE(date_sold) = ?), 0) as s_qty,
                    COALESCE((SELECT SUM(total_price) FROM milk_sales WHERE dairy_id = d.id AND DATE(date_sold) = ?), 0) as s_amt
                    FROM dairies d ORDER BY d.name ASC");
        $stmt->execute([$date, $date, $date, $date, $date]);
        return $stmt->fetchAll();
    }

    public function getMonthlyPerformanceBreakdown($date) {
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        $stmt = $this->pdo->prepare("SELECT d.name, 
            COALESCE((SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = d.id AND MONTH(date_collected) = ? AND YEAR(date_collected) = ?), 0) as c_qty,
            COALESCE((SELECT SUM(total_price) FROM milk_collection WHERE dairy_id = d.id AND MONTH(date_collected) = ? AND YEAR(date_collected) = ?), 0) as c_amt,
            (SELECT GROUP_CONCAT(DISTINCT sold_to SEPARATOR ', ') FROM milk_sales WHERE dairy_id = d.id AND MONTH(date_sold) = ? AND YEAR(date_sold) = ?) as buyers,
            COALESCE((SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = d.id AND MONTH(date_sold) = ? AND YEAR(date_sold) = ?), 0) as s_qty,
            COALESCE((SELECT SUM(total_price) FROM milk_sales WHERE dairy_id = d.id AND MONTH(date_sold) = ? AND YEAR(date_sold) = ?), 0) as s_amt
            FROM dairies d ORDER BY d.name ASC");
        $stmt->execute([$month, $year, $month, $year, $month, $year, $month, $year, $month, $year]);
        return $stmt->fetchAll();
    }

    public function getMonthlyDetailedSales($date) {
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        $stmt = $this->pdo->prepare("SELECT d.name, ms.sold_to, SUM(ms.quantity) as qty, SUM(ms.total_price) as amt 
                                     FROM milk_sales ms JOIN dairies d ON ms.dairy_id = d.id 
                                     WHERE MONTH(ms.date_sold) = ? AND YEAR(ms.date_sold) = ? 
                                     GROUP BY d.id, ms.sold_to ORDER BY d.name ASC, ms.sold_to ASC");
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }
}
?>