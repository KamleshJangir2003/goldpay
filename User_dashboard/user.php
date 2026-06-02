<?php
require_once __DIR__ . '/../config/db.php';

// Get current page from URL or default to dashboard
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Goldpay Pro Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    /* Your existing CSS styles here */
    :root {
      --primary: #6366f1;
      --secondary: #4f46e5;
      --background: #f8fafc;
      --surface: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
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
      display: flex;
      -webkit-font-smoothing: antialiased;
    }

    /* ======== Sidebar ======== */
    .sidebar {
      width: 280px;
      background: var(--surface);
      padding: 32px 24px;
      box-shadow: 2px 0 12px rgba(0,0,0,0.05);
      transition: 0.3s;
    }

    .sidebar-header {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 48px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 14px 18px;
      margin: 8px 0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
      color: var(--text-secondary);
    }

    .nav-item.active,
    .nav-item:hover {
      background: rgba(99, 102, 241, 0.08);
      color: var(--primary);
    }

    /* ======== Main Content ======== */
    .main-content {
      flex: 1;
      padding: 40px;
      display: grid;
      gap: 24px;
    }

    /* ======== Dashboard Grid ======== */
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
    }

    /* ======== Cards ======== */
    .dashboard-card {
      background: var(--surface);
      padding: 28px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      transition: transform 0.2s ease;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .card-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* ======== Live Price Graph ======== */
    .price-graph {
      height: 150px;
      background: var(--background);
      border-radius: 12px;
      margin: 20px 0;
      position: relative;
      overflow: hidden;
    }

    .graph-wave {
      position: absolute;
      height: 200%;
      width: 50%;
      background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
      animation: waveAnim 2s infinite linear;
    }

    /* ======== Transaction List ======== */
    .transaction-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .transaction-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px;
      background: var(--background);
      border-radius: 12px;
    }

    .transaction-details {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* ======== Page Content Styles ======== */
    .page-content {
      display: none;
      grid-column: 1 / -1;
    }

    .page-content.active {
      display: block;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .page-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .filter-options {
      display: flex;
      gap: 12px;
    }

    .filter-btn {
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid var(--background);
      background: var(--surface);
      cursor: pointer;
    }

    .filter-btn.active {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .history-table {
      width: 100%;
      border-collapse: collapse;
    }

    .history-table th {
      text-align: left;
      padding: 12px 16px;
      color: var(--text-secondary);
      font-weight: 500;
      border-bottom: 1px solid var(--background);
    }

    .history-table td {
      padding: 16px;
      border-bottom: 1px solid var(--background);
    }

    .transaction-type {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .type-deposit {
      color: #22c55e;
    }

    .type-withdrawal {
      color: #ef4444;
    }

    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .status-completed {
      background: rgba(34, 197, 94, 0.1);
      color: #22c55e;
    }

    .status-pending {
      background: rgba(234, 179, 8, 0.1);
      color: #eab308;
    }

    /* Buy/Sell Page Styles */
    .buy-sell-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    .buy-sell-card {
      background: var(--surface);
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-control {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }

    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    /* Referral Program Styles */
    .referral-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: var(--surface);
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      text-align: center;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary);
      margin: 8px 0;
    }

    .stat-label {
      color: var(--text-secondary);
    }

    .referral-link-container {
      background: var(--surface);
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      margin-bottom: 32px;
    }

    .referral-link {
      display: flex;
      gap: 12px;
      margin-top: 16px;
    }

    .referral-link input {
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }

    /* Support Page Styles */
    .support-options {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-bottom: 32px;
    }

    .support-card {
      background: var(--surface);
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      cursor: pointer;
      transition: transform 0.2s ease;
    }

    .support-card:hover {
      transform: translateY(-4px);
    }

    .support-card h3 {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    /* ======== Responsive Design ======== */
    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        left: -280px;
        z-index: 100;
        height: 100vh;
      }
      
      .sidebar.active {
        left: 0;
      }

      .main-content {
        padding: 24px;
        width: 100%;
      }
      
      .history-table {
        display: block;
        overflow-x: auto;
      }

      .buy-sell-container {
        grid-template-columns: 1fr;
      }

      .referral-stats {
        grid-template-columns: 1fr;
      }

      .support-options {
        grid-template-columns: 1fr;
      }
    }

    @keyframes waveAnim {
      0% { left: -50%; }
      100% { left: 150%; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
 

  <!-- Main Content -->
  <main class="main-content">
    <!-- Dashboard View -->
    <div class="dashboard-grid <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" id="dashboard-view">
      <!-- Wallet Summary -->
      <div class="dashboard-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="material-icons-round">account_balance_wallet</span>
            Wallet Summary
          </h3>
          <span class="material-icons-round">more_vert</span>
        </div>
        <div class="balance-display">
          <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">
            ₹92,450.50
          </div>
          <div style="display: flex; gap: 24px; margin-top: 20px;">
            <div>
              <div style="color: var(--text-secondary);">USDT Balance</div>
              <div style="font-size: 1.2rem; font-weight: 600;">150.25 USDT</div>
            </div>
            <div>
              <div style="color: var(--text-secondary);">INR Balance</div>
              <div style="font-size: 1.2rem; font-weight: 600;">₹75,000</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Market Overview -->
      <div class="dashboard-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="material-icons-round">trending_up</span>
            Market Overview
          </h3>
          <div style="color: var(--primary); font-weight: 600;">Live</div>
        </div>
        <div class="price-graph">
          <div class="graph-wave"></div>
        </div>
        <div class="market-stats">
          <div style="display: flex; justify-content: space-between; margin-top: 16px;">
            <div>
              <div style="color: var(--text-secondary);">Current Price</div>
              <div style="font-size: 1.5rem; font-weight: 700;">₹84.32</div>
            </div>
            <div>
              <div style="color: var(--text-secondary);">24h Change</div>
              <div style="color: #22c55e; font-weight: 700;">+2.45%</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="dashboard-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="material-icons-round">flash_on</span>
            Quick Actions
          </h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 20px;">
          <a href="?page=buysell&action=buy" style="background: var(--primary); color: white; padding: 16px; border: none; border-radius: 12px; display: flex; flex-direction: column; align-items: center; gap: 8px; text-decoration: none;">
            <span class="material-icons-round">download</span>
            Buy Crypto
          </a>
          <a href="?page=buysell&action=sell" style="background: var(--background); padding: 16px; border: none; border-radius: 12px; display: flex; flex-direction: column; align-items: center; gap: 8px; text-decoration: none;">
            <span class="material-icons-round">upload</span>
            Sell Crypto
          </a>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="dashboard-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="material-icons-round">notifications</span>
            Recent Activity
          </h3>
          <span class="material-icons-round">more_vert</span>
        </div>
        <div class="transaction-list">
          <div class="transaction-item">
            <div class="transaction-details">
              <span class="material-icons-round" style="color: #22c55e;">arrow_circle_up</span>
              <div>
                <div>Received from User123</div>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">2 hours ago</div>
              </div>
            </div>
            <div style="font-weight: 600; color: #22c55e;">+ ₹5,000</div>
          </div>
          <div class="transaction-item">
            <div class="transaction-details">
              <span class="material-icons-round" style="color: #ef4444;">arrow_circle_down</span>
              <div>
                <div>Sent to Exchange</div>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">5 hours ago</div>
              </div>
            </div>
            <div style="font-weight: 600; color: #ef4444;">- 50 USDT</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Transaction History View -->
    <div class="page-content <?php echo $currentPage === 'history' ? 'active' : ''; ?>" id="history-view">
      <div class="page-header">
        <h1 class="page-title">Transaction History</h1>
        <div class="filter-options">
          <button class="filter-btn active">All</button>
          <button class="filter-btn">Deposits</button>
          <button class="filter-btn">Withdrawals</button>
          <button class="filter-btn">Last 30 Days</button>
        </div>
      </div>
      
      <table class="history-table">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#TX78945612</td>
            <td>
              <div class="transaction-type">
                <span class="material-icons-round type-deposit">arrow_downward</span>
                <span>Deposit</span>
              </div>
            </td>
            <td>+ ₹15,000.00</td>
            <td>12 May 2023, 10:30 AM</td>
            <td><span class="status-badge status-completed">Completed</span></td>
          </tr>
          <tr>
            <td>#TX78945611</td>
            <td>
              <div class="transaction-type">
                <span class="material-icons-round type-withdrawal">arrow_upward</span>
                <span>Withdrawal</span>
              </div>
            </td>
            <td>- 100.00 USDT</td>
            <td>11 May 2023, 02:15 PM</td>
            <td><span class="status-badge status-completed">Completed</span></td>
          </tr>
          <tr>
            <td>#TX78945610</td>
            <td>
              <div class="transaction-type">
                <span class="material-icons-round type-deposit">arrow_downward</span>
                <span>Deposit</span>
              </div>
            </td>
            <td>+ ₹5,000.00</td>
            <td>10 May 2023, 09:45 AM</td>
            <td><span class="status-badge status-completed">Completed</span></td>
          </tr>
          <tr>
            <td>#TX78945609</td>
            <td>
              <div class="transaction-type">
                <span class="material-icons-round type-withdrawal">arrow_upward</span>
                <span>Withdrawal</span>
              </div>
            </td>
            <td>- 50.00 USDT</td>
            <td>8 May 2023, 04:30 PM</td>
            <td><span class="status-badge status-completed">Completed</span></td>
          </tr>
          <tr>
            <td>#TX78945608</td>
            <td>
              <div class="transaction-type">
                <span class="material-icons-round type-deposit">arrow_downward</span>
                <span>Deposit</span>
              </div>
            </td>
            <td>+ ₹25,000.00</td>
            <td>5 May 2023, 11:20 AM</td>
            <td><span class="status-badge status-completed">Completed</span></td>
          </tr>
          <tr>
            <td>#TX78945607</td>
            <td>
              <div class="transaction-type">
                <span class="material-icons-round type-withdrawal">arrow_upward</span>
                <span>Withdrawal</span>
              </div>
            </td>
            <td>- 200.00 USDT</td>
            <td>3 May 2023, 03:45 PM</td>
            <td><span class="status-badge status-pending">Pending</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Buy/Sell View -->
    <div class="page-content <?php echo $currentPage === 'buysell' ? 'active' : ''; ?>" id="buysell-view">
      <div class="page-header">
        <h1 class="page-title">
          <?php echo isset($_GET['action']) && $_GET['action'] === 'sell' ? 'Sell Crypto' : 'Buy Crypto'; ?>
        </h1>
      </div>
      
      <div class="buy-sell-container">
        <div class="buy-sell-card">
          <h2 style="margin-bottom: 24px;">Market Overview</h2>
          <div class="price-graph" style="height: 200px;">
            <div class="graph-wave"></div>
          </div>
          <div class="market-stats" style="margin-top: 24px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
              <div>Current Price:</div>
              <div style="font-weight: 600;">₹84.32</div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
              <div>24h Change:</div>
              <div style="color: #22c55e; font-weight: 600;">+2.45%</div>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <div>24h Volume:</div>
              <div style="font-weight: 600;">₹1.2B</div>
            </div>
          </div>
        </div>
        
        <div class="buy-sell-card">
          <h2 style="margin-bottom: 24px;">
            <?php echo isset($_GET['action']) && $_GET['action'] === 'sell' ? 'Sell USDT' : 'Buy USDT'; ?>
          </h2>
          
          <form>
            <div class="form-group">
              <label for="amount">Amount (<?php echo isset($_GET['action']) && $_GET['action'] === 'sell' ? 'USDT' : 'INR'; ?>)</label>
              <input type="number" id="amount" class="form-control" placeholder="Enter amount">
            </div>
            
            <div class="form-group">
              <label for="payment-method"><?php echo isset($_GET['action']) && $_GET['action'] === 'sell' ? 'Withdrawal Method' : 'Payment Method'; ?></label>
              <select id="payment-method" class="form-control">
                <option value="">Select method</option>
                <?php if (isset($_GET['action']) && $_GET['action'] === 'sell'): ?>
                  <option value="bank">Bank Transfer</option>
                  <option value="upi">UPI</option>
                <?php else: ?>
                  <option value="upi">UPI</option>
                  <option value="netbanking">Net Banking</option>
                  <option value="card">Credit/Debit Card</option>
                <?php endif; ?>
              </select>
            </div>
            
            <?php if (!isset($_GET['action']) || $_GET['action'] !== 'sell'): ?>
            <div class="form-group">
              <label for="usdt-amount">You will receive (USDT)</label>
              <input type="text" id="usdt-amount" class="form-control" placeholder="0.00" readonly>
            </div>
            <?php else: ?>
            <div class="form-group">
              <label for="inr-amount">You will receive (INR)</label>
              <input type="text" id="inr-amount" class="form-control" placeholder="0.00" readonly>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
              <span class="material-icons-round"><?php echo isset($_GET['action']) && $_GET['action'] === 'sell' ? 'upload' : 'download'; ?></span>
              <?php echo isset($_GET['action']) && $_GET['action'] === 'sell' ? 'Sell Now' : 'Buy Now'; ?>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Referral Program View -->
    <div class="page-content <?php echo $currentPage === 'referral' ? 'active' : ''; ?>" id="referral-view">
      <div class="page-header">
        <h1 class="page-title">Referral Program</h1>
      </div>
      
      <div class="referral-stats">
        <div class="stat-card">
          <div class="stat-label">Total Referrals</div>
          <div class="stat-value">24</div>
          <div class="stat-label">Active Users</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-label">Earned Commission</div>
          <div class="stat-value">₹5,250</div>
          <div class="stat-label">Lifetime Earnings</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-label">Pending Bonus</div>
          <div class="stat-value">₹1,200</div>
          <div class="stat-label">To be credited</div>
        </div>
      </div>
      
      <div class="referral-link-container">
        <h2 style="margin-bottom: 16px;">Your Referral Link</h2>
        <p style="color: var(--text-secondary);">Share this link with your friends and earn 10% commission on their trading fees for life!</p>
        
        <div class="referral-link">
          <input type="text" value="https://dollar.io/ref/rahulsharma123" readonly>
          <button class="btn btn-primary">
            <span class="material-icons-round">content_copy</span>
            Copy
          </button>
        </div>
      </div>
      
      <div style="background: var(--surface); padding: 24px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        <h2 style="margin-bottom: 16px;">How It Works</h2>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
          <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
              <span style="background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">1</span>
              <h3>Share Your Link</h3>
            </div>
            <p style="color: var(--text-secondary);">Share your unique referral link with friends and family</p>
          </div>
          
          <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
              <span style="background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">2</span>
              <h3>They Sign Up</h3>
            </div>
            <p style="color: var(--text-secondary);">Your friends sign up using your link and start trading</p>
          </div>
          
          <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
              <span style="background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">3</span>
              <h3>Earn Commission</h3>
            </div>
            <p style="color: var(--text-secondary);">Earn 10% of their trading fees credited to your account</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Support View -->
    <div class="page-content <?php echo $currentPage === 'support' ? 'active' : ''; ?>" id="support-view">
      <div class="page-header">
        <h1 class="page-title">Customer Support</h1>
      </div>
      
      <div class="support-options">
        <div class="support-card">
          <h3>
            <span class="material-icons-round">chat</span>
            Live Chat
          </h3>
          <p style="color: var(--text-secondary);">Get instant help from our support team 24/7</p>
        </div>
        
        <div class="support-card">
          <h3>
            <span class="material-icons-round">email</span>
            Email Support
          </h3>
          <p style="color: var(--text-secondary);">Send us an email and we'll respond within 24 hours</p>
        </div>
        
        <div class="support-card">
          <h3>
            <span class="material-icons-round">call</span>
            Phone Support
          </h3>
          <p style="color: var(--text-secondary);">Call us at +91 9876543210 (10AM - 7PM IST)</p>
        </div>
        
        <div class="support-card">
          <h3>
            <span class="material-icons-round">help_center</span>
            FAQ
          </h3>
          <p style="color: var(--text-secondary);">Find answers to common questions in our knowledge base</p>
        </div>
      </div>
      
      <div style="background: var(--surface); padding: 24px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); margin-top: 24px;">
        <h2 style="margin-bottom: 16px;">Submit a Ticket</h2>
        
        <form>
          <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" class="form-control" placeholder="Enter subject">
          </div>
          
          <div class="form-group">
            <label for="issue-type">Issue Type</label>
            <select id="issue-type" class="form-control">
              <option value="">Select issue type</option>
              <option value="deposit">Deposit Issue</option>
              <option value="withdrawal">Withdrawal Issue</option>
              <option value="trading">Trading Issue</option>
              <option value="account">Account Issue</option>
              <option value="other">Other</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" class="form-control" rows="5" placeholder="Describe your issue in detail"></textarea>
          </div>
          
          <div class="form-group">
            <label for="attachments">Attachments (optional)</label>
            <input type="file" id="attachments" class="form-control">
          </div>
          
          <button type="submit" class="btn btn-primary">
            <span class="material-icons-round">send</span>
            Submit Ticket
          </button>
        </form>
      </div>
    </div>
  </main>

  <script>
    // Mobile Menu Toggle
    const menuToggle = document.createElement('div');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '<span class="material-icons-round">menu</span>';
    document.body.appendChild(menuToggle);

    const sidebar = document.querySelector('.sidebar');
    
    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });

    // Live Price Simulation
    setInterval(() => {
      const priceElements = document.querySelectorAll('.market-stats div:nth-child(1) div:last-child');
      const changeElements = document.querySelectorAll('.market-stats div:nth-child(2) div:last-child');
      
      const newPrice = (84.32 + (Math.random() - 0.5)).toFixed(2);
      const change = ((newPrice - 84.32) / 84.32 * 100).toFixed(2);
      
      priceElements.forEach(el => el.textContent = ₹${newPrice});
      changeElements.forEach(el => {
        el.textContent = ${change >= 0 ? '+' : ''}${change}%;
        el.style.color = change >= 0 ? '#22c55e' : '#ef4444';
      });
    }, 3000);

    // Filter buttons functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
      button.addEventListener('click', () => {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        // Here you would typically filter the transactions
      });
    });

    // Copy referral link functionality
    const copyButton = document.querySelector('.referral-link .btn');
    if (copyButton) {
      copyButton.addEventListener('click', (e) => {
        e.preventDefault();
        const referralInput = document.querySelector('.referral-link input');
        referralInput.select();
        document.execCommand('copy');
        
        // Show copied message
        const originalText = copyButton.innerHTML;
        copyButton.innerHTML = '<span class="material-icons-round">check</span> Copied!';
        setTimeout(() => {
          copyButton.innerHTML = originalText;
        }, 2000);
      });
    }

    // Amount calculation for buy/sell
    const amountInput = document.getElementById('amount');
    if (amountInput) {
      amountInput.addEventListener('input', () => {
        const amount = parseFloat(amountInput.value) || 0;
        const usdtAmount = document.getElementById('usdt-amount');
        const inrAmount = document.getElementById('inr-amount');
        
        if (usdtAmount) {
          usdtAmount.value = (amount / 84.32).toFixed(2);
        }
        
        if (inrAmount) {
          inrAmount.value = (amount * 84.32).toFixed(2);
        }
      });
    }
  </script>
</body>
</html>