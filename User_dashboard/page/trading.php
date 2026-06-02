<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
require '../config/db.php';

// Get current page from URL
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'chart';

// DB se USDT sell rates fetch karo
try {
    $r1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='usdt_sell_rate_1'");
    $r2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='usdt_sell_rate_2'");
    $row1 = $r1 ? $r1->fetch(PDO::FETCH_ASSOC) : null;
    $row2 = $r2 ? $r2->fetch(PDO::FETCH_ASSOC) : null;
} catch (Exception $e) { $row1 = $row2 = null; }
$basePrice = $row1 ? floatval($row1['setting_value']) : 89.80;

function getHistoricalData($days = 30) {
    global $basePrice;
    $data = [];
    $timestamp = time() - ($days * 24 * 60 * 60);
    
    for ($i = 0; $i < ($days * 24); $i++) {
        $open = $basePrice + (rand(-100, 100) / 100);
        $close = $open + (rand(-50, 50) / 100);
        $high = max($open, $close) + (rand(0, 30) / 100);
        $low = min($open, $close) - (rand(0, 30) / 100);
        $volume = rand(100, 1000);
        
        $data[] = [
            'time' => $timestamp + ($i * 60 * 60),
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => $volume
        ];
    }
    
    return $data;
}

$historicalData = getHistoricalData(7); // 7 days of hourly data
$currentPrice = end($historicalData)['close'];
$priceChange = (($currentPrice - $historicalData[count($historicalData)-2]['close']) / $historicalData[count($historicalData)-2]['close']) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Goldpay Pro - USDT/INR Chart</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/lightweight-charts@3.8.0/dist/lightweight-charts.standalone.production.js"></script>
  <style>
    :root {
      --primary: #6366f1;
      --secondary: #4f46e5;
      --background: #f8fafc;
      --surface: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --success: #22c55e;
      --danger: #ef4444;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: var(--background);
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
      margin: 0;
    }

    /* ======== Main Content ======== */
    .main-content { margin-left: 250px; display: grid; gap: 24px; padding: 20px; min-width: 0; }

    /* ======== Page Header ======== */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .page-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* ======== Chart Container ======== */
    .chart-container {
      background: var(--surface);
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      padding: 24px;
      height: 600px;
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .price-display {
      display: flex;
      align-items: baseline;
      gap: 12px;
    }

    .current-price {
      font-size: 1.8rem;
      font-weight: 700;
    }

    .price-change {
      font-size: 1rem;
      font-weight: 600;
      color: <?php echo $priceChange >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;
    }

    .timeframe-selector {
      display: flex;
      gap: 8px;
    }

    .timeframe-btn {
      padding: 6px 12px;
      border-radius: 6px;
      background: var(--background);
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .timeframe-btn.active {
      background: var(--primary);
      color: white;
    }

    .chart-types {
      display: flex;
      gap: 20px;
      margin-top: 26px;
    }

    .chart-type-btn {
      padding: 6px 12px;
      border-radius: 6px;
      background: var(--background);
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .chart-type-btn.active {
      background: var(--primary);
      color: white;
    }

    #candlestick-chart {
      width: 100%;
      height: 500px;
    }

    @media (max-width: 768px) {
      .main-content { margin-left: 0 !important; padding: 10px; }
      .chart-container { padding: 14px; height: auto; }
      #candlestick-chart { height: 300px; }
      .chart-header { flex-direction: column; align-items: flex-start; gap: 12px; }
      .timeframe-selector { width: 100%; overflow-x: auto; padding-bottom: 8px; }
      .current-price { font-size: 1.3rem; }
      .chart-types { flex-wrap: wrap; gap: 8px; }
    }
    
  </style>
</head>
<body>
  <?php include('../sidebar.php'); ?>
  <?php include('../mobile_header.php'); ?>
  <main class="main-content">

   

    <div class="chart-container">
      <div class="chart-header">
        <div class="price-display">
          <span class="current-price">₹<?php echo number_format($currentPrice, 2); ?></span>
          <span class="price-change">
            <?php echo ($priceChange >= 0 ? '+' : '') . number_format($priceChange, 2); ?>%
          </span>
        </div>
        
        <div class="timeframe-selector">
  <button class="timeframe-btn" onclick="updateChartData(event, '1D')">1D</button>
  <button class="timeframe-btn" onclick="updateChartData(event, '5D')">5D</button>
  <button class="timeframe-btn active" onclick="updateChartData(event, '1M')">1M</button>
  <button class="timeframe-btn" onclick="updateChartData(event, '3M')">3M</button>
  <button class="timeframe-btn" onclick="updateChartData(event, '1Y')">1Y</button>
  <button class="timeframe-btn" onclick="updateChartData(event, 'ALL')">ALL</button>
</div>

      </div>

      <div id="candlestick-chart"></div>

      <div class="chart-types">
        <button class="chart-type-btn active" onclick="changeChartType(event, 'candlestick')">Candle</button>
        <button class="chart-type-btn" onclick="changeChartType(event, 'bar')">Bar</button>
        <button class="chart-type-btn" onclick="changeChartType(event, 'line')">Line</button>
<button class="chart-type-btn" onclick="changeChartType(event, 'area')">Area</button>
<button class="chart-type-btn" onclick="changeChartType(event, 'hollowCandle')">Hollow Candle</button>

      </div>
    </div>
  </main>

  <script>
  const chartContainer = document.getElementById('candlestick-chart');
  const chart = LightweightCharts.createChart(chartContainer, {
    layout: {
      backgroundColor: '#ffffff',
      textColor: '#1e293b',
    },
    grid: {
      vertLines: { color: '#f1f5f9' },
      horzLines: { color: '#f1f5f9' },
    },
    crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
    rightPriceScale: { borderColor: '#e2e8f0' },
    timeScale: { borderColor: '#e2e8f0', timeVisible: true },
  });

  const historicalData = <?php echo json_encode($historicalData); ?>;
  const formattedData = historicalData.map(item => ({
    time: item.time,
    open: item.open,
    high: item.high,
    low: item.low,
    close: item.close,
  }));

  let currentSeries = chart.addCandlestickSeries({
    upColor: '#22c55e',
    downColor: '#ef4444',
    borderDownColor: '#ef4444',
    borderUpColor: '#22c55e',
    wickDownColor: '#ef4444',
    wickUpColor: '#22c55e',
  });
  currentSeries.setData(formattedData);

  const volumeData = historicalData.map(item => ({
    time: item.time,
    value: item.volume,
    color: item.close >= item.open ? 'rgba(34, 197, 94, 0.5)' : 'rgba(239, 68, 68, 0.5)',
  }));
  let volumeSeries = chart.addHistogramSeries({
    color: 'rgba(99, 102, 241, 0.5)',
    priceFormat: { type: 'volume' },
    priceScaleId: '',
    scaleMargins: { top: 0.8, bottom: 0 },
  });
  volumeSeries.setData(volumeData);

  const priceLine = {
    price: formattedData[formattedData.length - 1].close,
    color: '#6366f1',
    lineWidth: 2,
    lineStyle: 2,
    axisLabelVisible: true,
    title: 'Current',
  };
  currentSeries.createPriceLine(priceLine);

  function changeChartType(event, type) {
    // Remove active class from all buttons
    document.querySelectorAll('.chart-type-btn').forEach(btn => {
      btn.classList.remove('active');
    });
    // Add active class to clicked button
    event.target.classList.add('active');

    // Remove old series
    chart.removeSeries(currentSeries);
    if (volumeSeries) chart.removeSeries(volumeSeries);

    // Add new chart type
    switch (type) {
      case 'line':
        currentSeries = chart.addLineSeries({ color: '#6366f1', lineWidth: 2 });
        currentSeries.setData(formattedData.map(d => ({ time: d.time, value: d.close })));
        break;
      case 'area':
        currentSeries = chart.addAreaSeries({
          topColor: 'rgba(99, 102, 241, 0.4)',
          bottomColor: 'rgba(99, 102, 241, 0.1)',
          lineColor: '#6366f1',
          lineWidth: 2,
        });
        currentSeries.setData(formattedData.map(d => ({ time: d.time, value: d.close })));
        break;
      case 'hollowCandle':
        currentSeries = chart.addCandlestickSeries({
          upColor: 'rgba(0,0,0,0)',
          downColor: 'rgba(0,0,0,0)',
          borderUpColor: '#22c55e',
          borderDownColor: '#ef4444',
          wickUpColor: '#22c55e',
          wickDownColor: '#ef4444',
        });
        currentSeries.setData(formattedData);
        break;
      default: // candlestick
        currentSeries = chart.addCandlestickSeries({
          upColor: '#22c55e',
          downColor: '#ef4444',
          borderDownColor: '#ef4444',
          borderUpColor: '#22c55e',
          wickDownColor: '#ef4444',
          wickUpColor: '#22c55e',
        });
        currentSeries.setData(formattedData);
    }

    // Add price line again
    currentSeries.createPriceLine(priceLine);

    // Re-add volume if needed
    if (type !== 'line' && type !== 'area') {
      volumeSeries = chart.addHistogramSeries({
        color: 'rgba(99, 102, 241, 0.5)',
        priceFormat: { type: 'volume' },
        priceScaleId: '',
        scaleMargins: { top: 0.8, bottom: 0 },
      });
      volumeSeries.setData(volumeData);
    } else {
      volumeSeries = null;
    }
  }
</script>
</body>
</html>