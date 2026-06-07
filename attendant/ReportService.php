    public function getAvailableStock($dairy_id) {
        $stmt = $this->pdo->prepare("SELECT SUM(quantity) FROM milk_collection WHERE dairy_id = ?");
        $stmt->execute([$dairy_id]);
        $collected = $stmt->fetchColumn() ?: 0;

        $stmt = $this->pdo->prepare("SELECT SUM(quantity) FROM milk_sales WHERE dairy_id = ?");
        $stmt->execute([$dairy_id]);
        $sold = $stmt->fetchColumn() ?: 0;

        return $collected - $sold;
    }