<?php
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NDMS - National Digital Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
<style>
  /* NDMS Modern Theme - National Digital Management System */
  :root {
    --primary-color: #1e3a8a;      /* Deep Blue - Government/Authority */
    --secondary-color: #3b82f6;    /* Bright Blue - Modern Tech */
    --accent-color: #10b981;       /* Emerald - Success/Progress */
    --warning-color: #f59e0b;      /* Amber - Attention */
    --danger-color: #ef4444;       /* Red - Critical */
    --light-bg: #f8fafc;          /* Light Gray Background */
    --card-bg: #ffffff;           /* Pure White Cards */
    --text-primary: #1f2937;      /* Dark Gray Text */
    --text-secondary: #6b7280;    /* Medium Gray Text */
    --border-color: #e5e7eb;      /* Light Border */
    --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  html {
    scroll-behavior: smooth;
  }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--light-bg);
    color: var(--text-primary);
    line-height: 1.6;
  }

  /* Navigation */
  nav {
    background: var(--card-bg);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: var(--shadow-sm);
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .logo i {
    color: var(--primary-color);
    font-size: 1.5rem;
  }

  .logo strong {
    color: var(--primary-color);
    font-size: 1.5rem;
    font-weight: 700;
  }

  .menu-toggle {
    display: none;
    font-size: 1.5rem;
    color: var(--primary-color);
    cursor: pointer;
    border: none;
    background: none;
  }

  .nav-links {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
  }

  .nav-links a {
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    padding: 0.5rem 0;
    position: relative;
  }

  .nav-links a:hover {
    color: var(--primary-color);
  }

  .nav-links a:hover::after {
    width: 100%;
  }

  .nav-links a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: width 0.3s ease;
  }

  .nav-links .login-btn {
    background: var(--gradient-bg);
    color: white;
    padding: 0.625rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
  }

  .nav-links .login-btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
    color: white;
  }

  .nav-links .login-btn::after {
    display: none;
  }

  /* Hero Section */
  .hero {
    position: relative;
    min-height: 80vh;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: white;
    overflow: hidden;
    background: var(--gradient-bg);
  }

  /* Animated Background Particles */
  .particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
  }

  .particle {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
  }

  .particle:nth-child(1) {
    width: 4px;
    height: 4px;
    left: 10%;
    animation-delay: 0s;
    animation-duration: 8s;
  }

  .particle:nth-child(2) {
    width: 6px;
    height: 6px;
    left: 20%;
    animation-delay: 1s;
    animation-duration: 7s;
  }

  .particle:nth-child(3) {
    width: 3px;
    height: 3px;
    left: 30%;
    animation-delay: 2s;
    animation-duration: 9s;
  }

  .particle:nth-child(4) {
    width: 5px;
    height: 5px;
    left: 40%;
    animation-delay: 0.5s;
    animation-duration: 6s;
  }

  .particle:nth-child(5) {
    width: 4px;
    height: 4px;
    left: 50%;
    animation-delay: 1.5s;
    animation-duration: 8s;
  }

  .particle:nth-child(6) {
    width: 7px;
    height: 7px;
    left: 60%;
    animation-delay: 3s;
    animation-duration: 7s;
  }

  .particle:nth-child(7) {
    width: 3px;
    height: 3px;
    left: 70%;
    animation-delay: 2.5s;
    animation-duration: 9s;
  }

  .particle:nth-child(8) {
    width: 5px;
    height: 5px;
    left: 80%;
    animation-delay: 1s;
    animation-duration: 6s;
  }

  .particle:nth-child(9) {
    width: 6px;
    height: 6px;
    left: 90%;
    animation-delay: 0.5s;
    animation-duration: 8s;
  }

  @keyframes float {
    0%, 100% {
      transform: translateY(100vh) rotate(0deg);
      opacity: 0;
    }
    10% {
      opacity: 1;
    }
    90% {
      opacity: 1;
    }
    100% {
      transform: translateY(-100px) rotate(360deg);
      opacity: 0;
    }
  }

  /* Gradient Animation */
  .hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, 
      var(--primary-color), 
      var(--secondary-color), 
      var(--accent-color), 
      var(--primary-color));
    background-size: 400% 400%;
    animation: gradientShift 8s ease infinite;
    opacity: 0.9;
    z-index: 0;
  }

  @keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  .hero video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    opacity: 0.2;
  }

  .hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    padding: 2rem;
    animation: fadeInUp 1s ease-out;
  }

  .hero-content h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* Typing Animation */
  .typing-text {
    border-right: 2px solid #ffffff;
    animation: blink 1s infinite;
  }

  @keyframes blink {
    0%, 50% { border-color: #ffffff; }
    51%, 100% { border-color: transparent; }
  }

  .hero-content p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    color: rgba(255, 255, 255, 0.9);
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    animation: fadeInUp 1s ease-out 0.3s both;
  }

  .hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeInUp 1s ease-out 0.6s both;
  }

  .cta-primary, .cta-secondary {
    padding: 0.875rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
  }

  .cta-primary {
    background: var(--accent-color);
    color: white;
    box-shadow: var(--shadow-md);
  }

  .cta-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
  }

  .cta-primary:hover::before {
    left: 100%;
  }

  .cta-primary:hover {
    background: #059669;
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
  }

  .cta-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
  }

  .cta-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
  }

  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  /* Sections */
  section {
    padding: 5rem 2rem;
    position: relative;
  }

  /* Floating Decorative Elements */
  .floating-shapes {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
  }

  .floating-shape {
    position: absolute;
    opacity: 0.1;
    animation: floatShapes 20s linear infinite;
  }

  .floating-shape.circle {
    border-radius: 50%;
    background: var(--primary-color);
  }

  .floating-shape.square {
    background: var(--accent-color);
  }

  .floating-shape.triangle {
    width: 0;
    height: 0;
    background: transparent;
    border-style: solid;
  }

  .floating-shape.triangle::before {
    content: '';
    position: absolute;
    border-left: 15px solid transparent;
    border-right: 15px solid transparent;
    border-bottom: 25px solid var(--secondary-color);
    top: -25px;
    left: -15px;
  }

  @keyframes floatShapes {
    0% {
      transform: translateY(100vh) rotate(0deg);
      opacity: 0;
    }
    10% {
      opacity: 0.1;
    }
    90% {
      opacity: 0.1;
    }
    100% {
      transform: translateY(-100px) rotate(360deg);
      opacity: 0;
    }
  }

  /* Add some geometric patterns to section backgrounds */
  .section-pattern {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    opacity: 0.03;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
    text-align: center;
  }

  .section-subtitle {
    font-size: 1.125rem;
    color: var(--text-secondary);
    text-align: center;
    max-width: 600px;
    margin: 0 auto 3rem auto;
  }

  /* Stats Section with Animations */
  .stats-section {
    background: var(--card-bg);
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
  }

  .stats-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
    animation: slideAcross 3s ease-in-out infinite;
  }

  @keyframes slideAcross {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    text-align: center;
    position: relative;
    z-index: 1;
  }

  .stat-card {
    padding: 2rem;
    background: var(--card-bg);
    border-radius: 1rem;
    box-shadow: var(--shadow-md);
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
  }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--gradient-bg);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
  }

  .stat-card:hover::before {
    transform: scaleX(1);
  }

  .stat-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-xl);
  }

  .stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    display: block;
    transition: all 0.3s ease;
  }

  .stat-card:hover .stat-number {
    color: var(--accent-color);
    transform: scale(1.1);
  }

  .stat-label {
    font-size: 1.125rem;
    color: var(--text-secondary);
    font-weight: 500;
  }

  /* Pulsing animation for stats */
  .stat-card.animate-pulse .stat-number {
    animation: pulse 2s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }

  /* Services Section with Enhanced Animations */
  .services-section {
    background: var(--light-bg);
    position: relative;
    overflow: hidden;
  }

  .services-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(circle at 10% 20%, rgba(30, 58, 138, 0.04) 0%, transparent 50%),
      radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 50%);
    pointer-events: none;
  }

  .services-section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(59,130,246,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23dots)"/></svg>');
    opacity: 0.6;
    pointer-events: none;
  }

  .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    position: relative;
    z-index: 1;
  }

  .service-card {
    background: var(--card-bg);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-md);
    transition: all 0.4s ease;
    border: 1px solid var(--border-color);
    text-align: left;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
  }

  .service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--gradient-bg);
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .service-card::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.6s ease;
    opacity: 0;
  }

  .service-card:hover::before {
    opacity: 0.05;
  }

  .service-card:hover::after {
    animation: shimmer 1.5s ease-in-out;
  }

  @keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
  }

  .service-card:hover {
    transform: translateY(-8px) rotate(1deg);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-color);
  }

  .service-icon {
    width: 4rem;
    height: 4rem;
    background: var(--gradient-bg);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
  }

  .service-card:hover .service-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: var(--shadow-lg);
  }

  .service-icon i {
    font-size: 1.5rem;
    color: white;
    transition: all 0.3s ease;
  }

  .service-card:hover .service-icon i {
    transform: scale(1.1);
  }

  .service-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
    transition: color 0.3s ease;
  }

  .service-card:hover h3 {
    color: var(--primary-color);
  }

  .service-card p {
    color: var(--text-secondary);
    line-height: 1.6;
    position: relative;
    z-index: 1;
    transition: color 0.3s ease;
  }

  .service-card:hover p {
    color: var(--text-primary);
  }

  /* Staggered animation for service cards */
  .service-card:nth-child(1) { animation-delay: 0.1s; }
  .service-card:nth-child(2) { animation-delay: 0.2s; }
  .service-card:nth-child(3) { animation-delay: 0.3s; }
  .service-card:nth-child(4) { animation-delay: 0.4s; }
  .service-card:nth-child(5) { animation-delay: 0.5s; }
  .service-card:nth-child(6) { animation-delay: 0.6s; }

  /* About Section */
  .about-section {
    background: var(--card-bg);
    position: relative;
    overflow: hidden;
  }

  .about-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
      radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.03) 0%, transparent 50%),
      radial-gradient(circle at 40% 40%, rgba(30, 58, 138, 0.02) 0%, transparent 50%);
    pointer-events: none;
  }

  .about-section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
      linear-gradient(45deg, rgba(59, 130, 246, 0.02) 25%, transparent 25%),
      linear-gradient(-45deg, rgba(59, 130, 246, 0.02) 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, rgba(59, 130, 246, 0.02) 75%),
      linear-gradient(-45deg, transparent 75%, rgba(59, 130, 246, 0.02) 75%);
    background-size: 60px 60px;
    background-position: 0 0, 0 30px, 30px -30px, -30px 0px;
    opacity: 0.3;
    pointer-events: none;
  }

  .about-content {
    max-width: 1000px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  .about-item {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
    margin-bottom: 4rem;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    padding: 2rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
  }

  .about-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
    background: rgba(255, 255, 255, 0.9);
  }

  .about-item:nth-child(even) {
    direction: rtl;
  }

  .about-item:nth-child(even) > * {
    direction: ltr;
  }

  .about-item img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 1rem;
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
  }

  .about-item:hover img {
    transform: scale(1.05);
    box-shadow: var(--shadow-xl);
  }

  .about-text h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
  }

  .about-text p {
    font-size: 1.125rem;
    line-height: 1.7;
    color: var(--text-secondary);
  }

  /* Search Section */
  .search-section {
    background: var(--gradient-bg);
    color: white;
    text-align: center;
  }

  .search-section h2 {
    color: white;
    margin-bottom: 1rem;
  }

  .search-section p {
    font-size: 1.125rem;
    margin-bottom: 2rem;
    opacity: 0.9;
  }

  .search-form {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    gap: 1rem;
    align-items: stretch;
  }

  .search-form input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid rgba(59, 130, 246, 0.2);
    border-radius: 0.75rem;
    font-size: 1.1rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    text-align: center;
    font-weight: 500;
    letter-spacing: 0.5px;
  }

  .search-form input:focus {
    outline: none;
    background: rgba(255, 255, 255, 1);
    box-shadow: var(--shadow-md);
  }

  .search-form button {
    padding: 1rem 2rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
    white-space: nowrap;
  }

  .search-form button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
  }

  .search-form button:hover::before {
    left: 100%;
  }

  .search-form button:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.4);
  }

  /* Contact Section */
  .contact-section {
    background: var(--light-bg);
    position: relative;
    overflow: hidden;
  }

  .contact-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.04) 0%, transparent 50%),
      radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 50%),
      linear-gradient(135deg, rgba(30, 58, 138, 0.02) 0%, rgba(16, 185, 129, 0.02) 100%);
    pointer-events: none;
  }

  .contact-section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="contact-hexagon" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><polygon points="30,0 52,15 52,45 30,60 8,45 8,15" fill="none" stroke="rgba(59,130,246,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(%23contact-hexagon)"/></svg>');
    opacity: 0.6;
    pointer-events: none;
  }

  .contact-form {
    max-width: 600px;
    margin: 0 auto;
    background: var(--card-bg);
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    position: relative;
    z-index: 1;
    backdrop-filter: blur(10px);
  }

  .contact-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    border-radius: 1rem;
    pointer-events: none;
  }

  .contact-form input,
  .contact-form textarea {
    width: 100%;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s ease;
  }

  .contact-form input:focus,
  .contact-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
  }

  .contact-form button {
    width: 100%;
    background: var(--gradient-bg);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .contact-form button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
  }

  /* Testimonials Section */
  .testimonials-section {
    background: var(--card-bg);
    position: relative;
    overflow: hidden;
  }

  .testimonials-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(ellipse at center, rgba(16, 185, 129, 0.05) 0%, transparent 70%),
      linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(16, 185, 129, 0.02) 100%);
    pointer-events: none;
  }

  .testimonials-section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><pattern id="testimonial-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse"><polygon points="20,0 40,20 20,40 0,20" fill="none" stroke="rgba(59,130,246,0.08)" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(%23testimonial-pattern)"/></svg>');
    opacity: 0.7;
    pointer-events: none;
  }

  .testimonials-container {
    max-width: 1000px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  .testimonials-slider {
    display: flex;
    transition: transform 0.5s ease;
  }

  .testimonial-card {
    min-width: 100%;
    padding: 3rem 2rem;
    text-align: center;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 1.5rem;
    margin: 0 1rem;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(59, 130, 246, 0.1);
    box-shadow: 
      0 10px 25px rgba(0, 0, 0, 0.08),
      0 4px 12px rgba(0, 0, 0, 0.05),
      inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 
      0 20px 40px rgba(0, 0, 0, 0.12),
      0 8px 20px rgba(0, 0, 0, 0.08),
      inset 0 1px 0 rgba(255, 255, 255, 0.9);
  }

  .testimonial-card::before {
    content: '"';
    position: absolute;
    top: 1rem;
    left: 2rem;
    font-size: 4rem;
    color: rgba(59, 130, 246, 0.1);
    font-family: serif;
    line-height: 1;
  }

  .testimonial-content {
    font-size: 1.25rem;
    line-height: 1.8;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    font-style: italic;
    position: relative;
    z-index: 1;
  }

  .testimonial-author {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
  }

  .author-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--gradient-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
  }

  .author-info h4 {
    color: var(--text-primary);
    margin-bottom: 0.25rem;
  }

  .author-info p {
    color: var(--text-secondary);
    font-size: 0.9rem;
  }

  .testimonial-nav {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
  }

  .nav-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--border-color);
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .nav-dot.active {
    background: var(--primary-color);
  }

  /* Dark Mode Testimonials */
  [data-theme="dark"] .testimonial-card {
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid var(--border-color);
  }

  [data-theme="dark"] .testimonial-content {
    color: var(--text-primary);
  }

  [data-theme="dark"] .testimonial-card::before {
    color: rgba(59, 130, 246, 0.2);
  }

  /* Dark Mode Service Cards */
  [data-theme="dark"] .service-card {
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid var(--border-color);
  }

  [data-theme="dark"] .service-card h3 {
    color: var(--text-primary);
  }

  [data-theme="dark"] .service-card p {
    color: var(--text-secondary);
  }

  [data-theme="dark"] .service-card:hover {
    background: rgba(30, 41, 59, 0.95);
    border-color: var(--secondary-color);
  }

  /* Dark Mode About Section Cards */
  [data-theme="dark"] .about-item {
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid var(--border-color);
  }

  [data-theme="dark"] .about-item h3 {
    color: var(--text-primary);
  }

  [data-theme="dark"] .about-item p {
    color: var(--text-secondary);
  }

  [data-theme="dark"] .about-item:hover {
    background: rgba(30, 41, 59, 0.95);
    border-color: var(--secondary-color);
  }

  /* Contact Information Cards */
  .contact-info-grid {
    margin-top: 3rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    text-align: center;
  }

  .contact-info-card {
    background: rgba(30, 58, 138, 0.1);
    backdrop-filter: blur(10px);
    padding: 1.5rem;
    border-radius: 1rem;
    border: 1px solid rgba(30, 58, 138, 0.2);
    transition: all 0.3s ease;
  }

  .contact-info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(30, 58, 138, 0.2);
  }

  .contact-info-card .contact-icon {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
    display: block;
  }

  .contact-info-card h3 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-weight: 600;
  }

  .contact-info-card .contact-primary {
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  .contact-info-card .contact-secondary {
    color: var(--text-secondary);
    font-size: 0.9rem;
  }

  /* Dark Mode Contact Information */
  [data-theme="dark"] .contact-info-card {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  [data-theme="dark"] .contact-info-card .contact-icon {
    color: white;
  }

  [data-theme="dark"] .contact-info-card h3 {
    color: white;
  }

  [data-theme="dark"] .contact-info-card .contact-primary {
    color: rgba(255, 255, 255, 0.9);
  }

  [data-theme="dark"] .contact-info-card .contact-secondary {
    color: rgba(255, 255, 255, 0.7);
  }

  [data-theme="dark"] .contact-info-card:hover {
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  }

  /* FAQ Section */
  .faq-section {
    background: var(--light-bg);
    position: relative;
    overflow: hidden;
  }

  .faq-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(circle at 25% 25%, rgba(30, 58, 138, 0.03) 0%, transparent 50%),
      radial-gradient(circle at 75% 75%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
    pointer-events: none;
  }

  .faq-section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="faq-grid" x="0" y="0" width="25" height="25" patternUnits="userSpaceOnUse"><path d="M 25 0 L 0 0 0 25" fill="none" stroke="rgba(59,130,246,0.06)" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(%23faq-grid)"/></svg>');
    pointer-events: none;
  }

  .faq-container {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  .faq-item {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    position: relative;
  }

  .faq-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: var(--gradient-bg);
    transform: scaleY(0);
    transform-origin: bottom;
    transition: transform 0.3s ease;
  }

  .faq-item:hover::before,
  .faq-item.active::before {
    transform: scaleY(1);
  }

  .faq-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateX(5px);
  }

  .faq-question {
    padding: 1.5rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--card-bg);
    transition: background 0.3s ease;
  }

  .faq-question:hover {
    background: var(--light-bg);
  }

  .faq-question h3 {
    color: var(--text-primary);
    font-size: 1.125rem;
    font-weight: 600;
  }

  .faq-icon {
    color: var(--primary-color);
    font-size: 1.2rem;
    transition: transform 0.3s ease;
  }

  .faq-item.active .faq-icon {
    transform: rotate(180deg);
  }

  .faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
  }

  .faq-item.active .faq-answer {
    max-height: 200px;
  }

  .faq-answer-content {
    padding: 0 1.5rem 1.5rem;
    color: var(--text-secondary);
    line-height: 1.6;
  }

  /* Newsletter Section */
  .newsletter-section {
    background: linear-gradient(135deg, var(--accent-color) 0%, #059669 100%);
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .newsletter-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="75" cy="75" r="15" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') repeat;
    opacity: 0.3;
  }

  .newsletter-form {
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .newsletter-input {
    flex: 1;
    min-width: 250px;
    padding: 1rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    color: white;
    border: 2px solid transparent;
    transition: all 0.3s ease;
  }

  .newsletter-input::placeholder {
    color: rgba(255, 255, 255, 0.7);
  }

  .newsletter-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
  }

  .newsletter-btn {
    padding: 1rem 2rem;
    background: var(--accent-color);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
  }

  .newsletter-btn:hover {
    background: #059669;
    transform: translateY(-2px);
  }

  /* Add CSS for enhanced visual effects */
  .section-decoration {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    overflow: hidden;
  }

  .section-decoration::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: 
      radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
      radial-gradient(circle at 75% 75%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
    animation: rotateBackground 60s linear infinite;
  }

  @keyframes rotateBackground {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Enhanced glassmorphism effect */
  .glassmorphism {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
  }

  /* Pulsing glow effect for important elements */
  .glow-pulse {
    animation: glowPulse 3s ease-in-out infinite alternate;
  }

  @keyframes glowPulse {
    from {
      box-shadow: 0 0 5px rgba(59, 130, 246, 0.2), 0 0 10px rgba(59, 130, 246, 0.2), 0 0 15px rgba(59, 130, 246, 0.2);
    }
    to {
      box-shadow: 0 0 10px rgba(59, 130, 246, 0.4), 0 0 20px rgba(59, 130, 246, 0.4), 0 0 30px rgba(59, 130, 246, 0.4);
    }
  }

  /* Breathing animation for subtle movement */
  .breathing {
    animation: breathe 4s ease-in-out infinite;
  }

  @keyframes breathe {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
  }

  /* Floating icons background */
  .floating-icons {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    overflow: hidden;
  }

  .floating-icon {
    position: absolute;
    font-size: 1.5rem;
    color: rgba(59, 130, 246, 0.08);
    animation: floatIcons 25s linear infinite;
  }

  .floating-icon:nth-child(1) { left: 10%; animation-delay: 0s; }
  .floating-icon:nth-child(2) { left: 20%; animation-delay: 5s; }
  .floating-icon:nth-child(3) { left: 30%; animation-delay: 10s; }
  .floating-icon:nth-child(4) { left: 40%; animation-delay: 15s; }
  .floating-icon:nth-child(5) { left: 50%; animation-delay: 20s; }
  .floating-icon:nth-child(6) { left: 60%; animation-delay: 2s; }
  .floating-icon:nth-child(7) { left: 70%; animation-delay: 7s; }
  .floating-icon:nth-child(8) { left: 80%; animation-delay: 12s; }
  .floating-icon:nth-child(9) { left: 90%; animation-delay: 17s; }

  @keyframes floatIcons {
    0% {
      transform: translateY(100vh) rotate(0deg);
      opacity: 0;
    }
    5% {
      opacity: 0.8;
    }
    95% {
      opacity: 0.8;
    }
    100% {
      transform: translateY(-100px) rotate(360deg);
      opacity: 0;
    }
  }

  /* Dark Mode Support */
  [data-theme="dark"] {
    --primary-color: #3b82f6;
    --secondary-color: #60a5fa;
    --accent-color: #34d399;
    --light-bg: #0f172a;
    --card-bg: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --border-color: #334155;
  }

  /* Dark Mode Toggle */
  .theme-toggle {
    position: fixed;
    top: 50%;
    right: 2rem;
    transform: translateY(-50%);
    z-index: 1000;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 50%;
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-md);
  }

  .theme-toggle:hover {
    transform: translateY(-50%) scale(1.1);
    box-shadow: var(--shadow-lg);
  }

  .theme-toggle i {
    font-size: 1.2rem;
    color: var(--primary-color);
    transition: all 0.3s ease;
  }

  /* Scroll Progress Bar */
  .scroll-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: var(--gradient-bg);
    z-index: 1001;
    transition: width 0.1s ease;
  }

  /* Loading Animation */
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.5s ease;
  }

  .loading-overlay.hidden {
    opacity: 0;
    pointer-events: none;
  }

  .loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid var(--border-color);
    border-top: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Notification Toast */
  .toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--card-bg);
    color: var(--text-primary);
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
  }

  .toast.show {
    transform: translateY(0);
    opacity: 1;
  }

  .toast.success {
    border-left: 4px solid var(--accent-color);
  }

  .toast.error {
    border-left: 4px solid var(--danger-color);
  }

  /* Footer */
  footer {
    background: var(--text-primary);
    color: white;
    padding: 3rem 2rem 2rem;
    position: relative;
  }

  [data-theme="dark"] footer {
    background: #0f172a;
    border-top: 1px solid var(--border-color);
  }

  .footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    position: relative;
    z-index: 1;
  }

  .footer-section h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: white;
  }

  [data-theme="dark"] .footer-section h3 {
    color: var(--text-primary);
  }

  .footer-section p,
  .footer-section a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    line-height: 1.6;
    transition: color 0.3s ease;
  }

  [data-theme="dark"] .footer-section p,
  [data-theme="dark"] .footer-section a {
    color: var(--text-secondary);
  }

  .footer-section a:hover {
    color: white;
  }

  [data-theme="dark"] .footer-section a:hover {
    color: var(--text-primary);
  }

  .footer-bottom {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
  }

  [data-theme="dark"] .footer-bottom {
    border-top-color: var(--border-color);
    color: var(--text-secondary);
  }

  /* Dark Mode Support */
  [data-theme="dark"] {
    --primary-color: #3b82f6;
    --secondary-color: #60a5fa;
    --accent-color: #34d399;
    --light-bg: #0f172a;
    --card-bg: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --border-color: #334155;
  }

  /* Dark Mode Toggle */
  .theme-toggle {
    position: fixed;
    top: 50%;
    right: 2rem;
    transform: translateY(-50%);
    z-index: 1000;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 50%;
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-md);
  }

  .theme-toggle:hover {
    transform: translateY(-50%) scale(1.1);
    box-shadow: var(--shadow-lg);
  }

  .theme-toggle i {
    font-size: 1.2rem;
    color: var(--primary-color);
    transition: all 0.3s ease;
  }

  /* Scroll Progress Bar */
  .scroll-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: var(--gradient-bg);
    z-index: 1001;
    transition: width 0.1s ease;
  }

  /* Loading Animation */
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.5s ease;
  }

  .loading-overlay.hidden {
    opacity: 0;
    pointer-events: none;
  }

  .loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid var(--border-color);
    border-top: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Notification Toast */
  .toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--card-bg);
    color: var(--text-primary);
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
  }

  .toast.show {
    transform: translateY(0);
    opacity: 1;
  }

  .toast.success {
    border-left: 4px solid var(--accent-color);
  }

  .toast.error {
    border-left: 4px solid var(--danger-color);
  }
  @media (max-width: 768px) {
    nav {
      padding: 1rem;
    }

    .menu-toggle {
      display: block;
    }

    .nav-links {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: var(--card-bg);
      flex-direction: column;
      gap: 0;
      box-shadow: var(--shadow-lg);
      border-top: 1px solid var(--border-color);
      padding: 1rem;
    }

    .nav-links.active {
      display: flex;
    }

    .nav-links a {
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--border-color);
    }

    .nav-links a:last-child {
      border-bottom: none;
    }

    .hero-content h1 {
      font-size: 2.5rem;
    }

    .hero-content p {
      font-size: 1.125rem;
    }

    .hero-buttons {
      flex-direction: column;
      align-items: center;
    }

    .cta-primary, .cta-secondary {
      width: 100%;
      max-width: 300px;
      justify-content: center;
    }

    .stats-grid {
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }

    .services-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }

    .about-item {
      grid-template-columns: 1fr;
      gap: 2rem;
      text-align: center;
    }

    .about-item:nth-child(even) {
      direction: ltr;
    }

    section {
      padding: 3rem 1rem;
    }

    h2 {
      font-size: 2rem;
    }

    .search-form {
      flex-direction: column;
      padding: 0 1rem;
    }
  }

  @media (max-width: 480px) {
    .hero-content h1 {
      font-size: 2rem;
    }

    .stat-number {
      font-size: 2.5rem;
    }

    .service-card,
    .stat-card {
      padding: 1.5rem;
    }

    .contact-form {
      padding: 1.5rem;
      margin: 0 1rem;
    }
  }
</style>

</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-spinner"></div>
</div>

<!-- Scroll Progress Bar -->
<div class="scroll-progress" id="scrollProgress"></div>

<!-- Dark Mode Toggle -->
<div class="theme-toggle" id="themeToggle">
  <i class="fas fa-moon" id="themeIcon"></i>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast"></div>

<nav>
  <div class="logo">
    <i class="fas fa-shield-alt"></i>
    <strong>NDMS</strong>
  </div>
  <button class="menu-toggle" id="mobile-menu">
    <i class="fas fa-bars"></i>
  </button>
  <div class="nav-links" id="nav-links">
    <a href="#home">Home</a>
    <a href="#about">About</a>
    <a href="#services">Services</a>
    <a href="#testimonials">Reviews</a>
    <a href="#faq">FAQ</a>
    <a href="#contact">Contact</a>
    <a href="login.php" class="login-btn">
      <i class="fas fa-sign-in-alt"></i>
      Login
    </a>
  </div>
</nav>

<!-- Hero Section -->
<section id="home" class="hero">
  <div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>
  <video autoplay muted loop playsinline>
    <source src="Video/home.mp4" type="video/mp4" />
    Your browser does not support the video tag.
  </video>
  <div class="hero-content">
    <h1><span class="typing-text">National Digital Management System</span></h1>
    <p>A comprehensive digital identity solution that unifies essential personal information across all life stages. Secure, efficient, and designed for Sri Lanka's digital future.</p>
    <div class="hero-buttons">
      <a href="login.php" class="cta-primary">
        <i class="fas fa-rocket"></i>
        Get Started
      </a>
      <a href="#about" class="cta-secondary">
        <i class="fas fa-info-circle"></i>
        Learn More
      </a>
    </div>
  </div>
</section>
<!-- About Section -->
<section id="about" class="about-section">
  <div class="floating-shapes">
    <div class="floating-shape circle" style="width: 20px; height: 20px; left: 10%; animation-delay: 0s; animation-duration: 15s;"></div>
    <div class="floating-shape square" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s; animation-duration: 18s;"></div>
    <div class="floating-shape triangle" style="left: 30%; animation-delay: 4s; animation-duration: 22s;"></div>
    <div class="floating-shape circle" style="width: 25px; height: 25px; left: 50%; animation-delay: 1s; animation-duration: 16s;"></div>
    <div class="floating-shape square" style="width: 18px; height: 18px; left: 70%; animation-delay: 3s; animation-duration: 20s;"></div>
    <div class="floating-shape circle" style="width: 22px; height: 22px; left: 85%; animation-delay: 5s; animation-duration: 17s;"></div>
  </div>
  <div class="container">
    <h2>About NDMS</h2>
    <p class="section-subtitle">Revolutionizing digital identity management for the modern era</p>
    
    <div class="about-content">
      <div class="about-item glassmorphism breathing" data-aos="fade-right" data-aos-delay="200">
        <img src="Image/digi.jpg" alt="Digital Platform"/>
        <div class="about-text">
          <h3>Comprehensive Digital Platform</h3>
          <p>The National Digital Management System is a forward-thinking digital platform that unifies essential personal information across different life stages. Designed with national scalability in mind, this system provides a centralized, secure way to store and access critical data from birth to employment and beyond.</p>
        </div>
      </div>

      <div class="about-item glassmorphism breathing" data-aos="fade-left" data-aos-delay="300">
        <img src="Image/id.jpg" alt="Smart Identity Card"/>
        <div class="about-text">
          <h3>Smart Digital Identity</h3>
          <p>At the heart of the system is a smart digital identity card embedded with QR/Barcode technology. This single card stores multiple types of verified information including academic history, extracurricular achievements, disciplinary records, and employment data.</p>
        </div>
      </div>

      <div class="about-item glassmorphism breathing" data-aos="fade-right" data-aos-delay="400">
        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=600&q=80" alt="Comprehensive Data"/>
        <div class="about-text">
          <h3>Comprehensive Dashboard</h3>
          <p>The platform offers a comprehensive dashboard where authorized personnel can search, review, and verify various records like educational qualifications, university involvement, and legal history, facilitating informed decision-making.</p>
        </div>
      </div>

      <div class="about-item glassmorphism breathing" data-aos="fade-left" data-aos-delay="500">
        <img src="Image/eco.jpg" alt="Eco-friendly"/>
        <div class="about-text">
          <h3>Sustainable & Efficient</h3>
          <p>By digitizing identity verification and record-keeping, the system drastically reduces paper usage and administrative overhead. This eco-friendly approach improves processing speed, accuracy, and national data integration.</p>
        </div>
      </div>

      <div class="about-item glassmorphism breathing" data-aos="fade-right" data-aos-delay="600">
        <img src="Image/lok.jpg" alt="Security"/>
        <div class="about-text">
          <h3>Maximum Security</h3>
          <p>Built with strong encryption and role-based access control, ensuring maximum data security and privacy. Sensitive personal and institutional data are securely stored and only accessible by verified authorities.</p>
        </div>
      </div>

      <div class="about-item glassmorphism breathing" data-aos="fade-left" data-aos-delay="700">
        <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80" alt="Future Technology"/>
        <div class="about-text">
          <h3>Future-Ready System</h3>
          <p>This system represents a leap into the future of personal data management in Sri Lanka, revolutionizing how information is accessed and shared across schools, universities, employment boards, and legal institutions.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
  <div class="container">
    <h2>Trusted Nationwide</h2>
    <p class="section-subtitle">Our impact across Sri Lanka's digital infrastructure</p>
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-number">10+</span>
        <span class="stat-label">Years in Development</span>
      </div>
      <div class="stat-card">
        <span class="stat-number">500+</span>
        <span class="stat-label">Institutions Connected</span>
      </div>
      <div class="stat-card">
        <span class="stat-number">1M+</span>
        <span class="stat-label">Citizens Registered</span>
      </div>
      <div class="stat-card">
        <span class="stat-number">99.9%</span>
        <span class="stat-label">System Uptime</span>
      </div>
    </div>
  </div>
</section>

<!-- Services Section -->
<section id="services" class="services-section">
  <div class="floating-shapes">
    <div class="floating-shape square" style="width: 12px; height: 12px; left: 15%; animation-delay: 1s; animation-duration: 19s;"></div>
    <div class="floating-shape circle" style="width: 18px; height: 18px; left: 35%; animation-delay: 3s; animation-duration: 21s;"></div>
    <div class="floating-shape triangle" style="left: 55%; animation-delay: 2s; animation-duration: 17s;"></div>
    <div class="floating-shape circle" style="width: 16px; height: 16px; left: 75%; animation-delay: 4s; animation-duration: 23s;"></div>
    <div class="floating-shape square" style="width: 20px; height: 20px; left: 90%; animation-delay: 0.5s; animation-duration: 18s;"></div>
  </div>
  <div class="floating-icons">
    <i class="floating-icon fas fa-graduation-cap"></i>
    <i class="floating-icon fas fa-certificate"></i>
    <i class="floating-icon fas fa-briefcase"></i>
    <i class="floating-icon fas fa-shield-alt"></i>
    <i class="floating-icon fas fa-address-card"></i>
    <i class="floating-icon fas fa-tools"></i>
    <i class="floating-icon fas fa-database"></i>
    <i class="floating-icon fas fa-lock"></i>
    <i class="floating-icon fas fa-user-check"></i>
  </div>
  <div class="container">
    <h2 data-aos="fade-up">Our Services</h2>
    <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Comprehensive digital identity management solutions</p>
    <div class="services-grid">
      <div class="service-card" data-aos="fade-up" data-aos-delay="100">
        <div class="service-icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h3>Educational Records</h3>
        <p>Complete history of schools and universities attended, including academic achievements, subject records, and institutional details for comprehensive educational tracking.</p>
      </div>
      
      <div class="service-card" data-aos="fade-up" data-aos-delay="200">
        <div class="service-icon">
          <i class="fas fa-certificate"></i>
        </div>
        <h3>Examination & Activities</h3>
        <p>Track public examination results and participation in extracurricular activities from school to university level with verified documentation.</p>
      </div>
      
      <div class="service-card" data-aos="fade-up" data-aos-delay="300">
        <div class="service-icon">
          <i class="fas fa-briefcase"></i>
        </div>
        <h3>Employment Information</h3>
        <p>Detailed records of current and previous employment history, including job roles, experience duration, and professional achievements.</p>
      </div>
      
      <div class="service-card" data-aos="fade-up" data-aos-delay="400">
        <div class="service-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3>Legal & Health Records</h3>
        <p>Secure documentation of legal background, disciplinary actions, criminal records, and chronic health conditions when applicable.</p>
      </div>
      
      <div class="service-card" data-aos="fade-up" data-aos-delay="500">
        <div class="service-icon">
          <i class="fas fa-address-card"></i>
        </div>
        <h3>Personal Information</h3>
        <p>Registered residence with Grama Niladhari information, contact details, and verified social media profiles for complete identity verification.</p>
      </div>
      
      <div class="service-card" data-aos="fade-up" data-aos-delay="600">
        <div class="service-icon">
          <i class="fas fa-tools"></i>
        </div>
        <h3>Skills & Certifications</h3>
        <p>Documentation of additional qualifications, short courses, professional certifications, and specialized training programs completed.</p>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials" class="testimonials-section">
  <div class="container">
    <h2 data-aos="fade-up">What People Say</h2>
    <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Trusted by citizens and institutions across Sri Lanka</p>
    
    <div class="testimonials-container" data-aos="fade-up" data-aos-delay="200">
      <div class="testimonials-slider" id="testimonialsSlider">
        <div class="testimonial-card">
          <div class="testimonial-content">
            "NDMS has revolutionized how we verify student credentials. The system is incredibly secure and efficient, making our admission process much smoother."
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">DR</div>
            <div class="author-info">
              <h4>Dr. Rajesh Perera</h4>
              <p>University Registrar, University of Colombo</p>
            </div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="testimonial-content">
            "As an employer, NDMS gives me confidence in the authenticity of candidate qualifications. The comprehensive background checks save us significant time and resources."
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">SM</div>
            <div class="author-info">
              <h4>Samantha Mendis</h4>
              <p>HR Director, Tech Solutions Lanka</p>
            </div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="testimonial-content">
            "The digital identity card has made accessing government services so much easier. Everything is in one place, and the security features give me peace of mind."
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">KS</div>
            <div class="author-info">
              <h4>Kasun Silva</h4>
              <p>Citizen, Galle District</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="testimonial-nav">
        <div class="nav-dot active" data-slide="0"></div>
        <div class="nav-dot" data-slide="1"></div>
        <div class="nav-dot" data-slide="2"></div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="faq-section">
  <div class="floating-shapes">
    <div class="floating-shape triangle" style="left: 8%; animation-delay: 2s; animation-duration: 20s;"></div>
    <div class="floating-shape circle" style="width: 14px; height: 14px; left: 25%; animation-delay: 1s; animation-duration: 16s;"></div>
    <div class="floating-shape square" style="width: 16px; height: 16px; left: 45%; animation-delay: 3s; animation-duration: 24s;"></div>
    <div class="floating-shape circle" style="width: 19px; height: 19px; left: 65%; animation-delay: 0s; animation-duration: 18s;"></div>
    <div class="floating-shape triangle" style="left: 85%; animation-delay: 4s; animation-duration: 22s;"></div>
  </div>
  <div class="container">
    <h2 data-aos="fade-up">Frequently Asked Questions</h2>
    <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Get answers to common questions about NDMS</p>
    
    <div class="faq-container">
      <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
        <div class="faq-question">
          <h3>How secure is my personal data in NDMS?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
          <div class="faq-answer-content">
            NDMS uses advanced encryption protocols and multi-layered security measures to protect your data. All information is stored in secure government servers with role-based access control, ensuring only authorized personnel can access your information.
          </div>
        </div>
      </div>
      
      <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
        <div class="faq-question">
          <h3>How do I register for NDMS?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
          <div class="faq-answer-content">
            Registration is handled through authorized government offices and educational institutions. Visit your nearest Divisional Secretariat office with required documents including birth certificate, NIC, and educational certificates to begin the registration process.
          </div>
        </div>
      </div>
      
      <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
        <div class="faq-question">
          <h3>Can I update my information in the system?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
          <div class="faq-answer-content">
            Yes, you can update certain information through authorized channels. Contact your institution or visit a government service center with proper documentation to update your records. Some updates may require verification from relevant authorities.
          </div>
        </div>
      </div>
      
      <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
        <div class="faq-question">
          <h3>Who can access my NDMS information?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
          <div class="faq-answer-content">
            Access is strictly controlled and limited to authorized personnel including education officers, medical officers, employers (with your consent), and relevant government officials. All access is logged and monitored for security purposes.
          </div>
        </div>
      </div>
      
      <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
        <div class="faq-question">
          <h3>What if I lose my digital identity card?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
          <div class="faq-answer-content">
            If your digital card is lost or stolen, immediately report it to the nearest NDMS office or through the emergency hotline. Your account will be temporarily suspended for security, and a replacement card will be issued after identity verification.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section">
  <div class="container">
    <h2 data-aos="fade-up">Stay Updated</h2>
    <p data-aos="fade-up" data-aos-delay="100">Subscribe to receive important updates and announcements about NDMS</p>
    <form class="newsletter-form" id="newsletterForm" data-aos="fade-up" data-aos-delay="200">
      <input type="email" class="newsletter-input" name="email" placeholder="Enter your email address" required>
      <button type="submit" class="newsletter-btn">
        <i class="fas fa-paper-plane"></i>
        Subscribe
      </button>
    </form>
    <div id="newsletter-response" style="margin-top: 1rem; text-align: center; font-weight: 600;"></div>
  </div>
</section>

<!-- Search Section -->
<section class="search-section">
  <div class="container">
    <h2>Citizen Information Search</h2>
    <p>Enter a citizen's eID to view their public profile information</p>
    <form class="search-form" action="public_profile.php" method="get">
      <input type="text" name="eid" placeholder="Enter Citizen eID (e.g., CID001, CID002)" required 
             pattern="[A-Za-z0-9]+" title="Please enter a valid eID" />
      <button type="submit">
        <i class="fas fa-search"></i>
        View Public Profile
      </button>
    </form>
    <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 1rem;">
      <i class="fas fa-info-circle"></i> This search will display publicly available information only
    </p>
  </div>
</section>


<!-- Contact Section -->
<section id="contact" class="contact-section">
  <div class="floating-shapes">
    <div class="floating-shape circle" style="width: 17px; height: 17px; left: 12%; animation-delay: 1.5s; animation-duration: 19s;"></div>
    <div class="floating-shape square" style="width: 14px; height: 14px; left: 28%; animation-delay: 3.5s; animation-duration: 21s;"></div>
    <div class="floating-shape triangle" style="left: 48%; animation-delay: 0.5s; animation-duration: 17s;"></div>
    <div class="floating-shape circle" style="width: 21px; height: 21px; left: 68%; animation-delay: 2.5s; animation-duration: 23s;"></div>
    <div class="floating-shape square" style="width: 19px; height: 19px; left: 88%; animation-delay: 4.5s; animation-duration: 18s;"></div>
  </div>
  <div class="container">
    <h2>Contact Us</h2>
    <p class="section-subtitle">Get in touch with our team for support and inquiries</p>
    <form class="contact-form" method="post">
      <input type="text" name="name" placeholder="Your Full Name" required maxlength="100"/>
      <input type="email" name="email" placeholder="Your Email Address" required maxlength="150"/>
      <textarea name="message" rows="5" placeholder="Your Message or Inquiry" required maxlength="5000"></textarea>
      <button type="submit">
        <i class="fas fa-paper-plane"></i>
        Send Message
      </button>
    </form>
    
    <!-- Contact Information -->
    <div class="contact-info-grid">
      <div class="contact-info-card">
        <i class="fas fa-phone contact-icon"></i>
        <h3>Phone Support</h3>
        <p class="contact-primary">+94 78 093 8755</p>
        <p class="contact-secondary">Mon-Fri: 8AM-5PM</p>
      </div>
      
      <div class="contact-info-card">
        <i class="fas fa-envelope contact-icon"></i>
        <h3>Email Support</h3>
        <p class="contact-primary">ndms@gov.lk</p>
        <p class="contact-secondary">Response within 24 hours</p>
      </div>
      
      <div class="contact-info-card">
        <i class="fas fa-map-marker-alt contact-icon"></i>
        <h3>Visit Office</h3>
        <p class="contact-primary">Government Digital Hub</p>
        <p class="contact-secondary">Galle, Sri Lanka</p>
      </div>
      
      <div class="contact-info-card">
        <i class="fas fa-clock contact-icon"></i>
        <h3>Emergency Support</h3>
        <p class="contact-primary">Available 24/7</p>
        <p class="contact-secondary">For urgent issues only</p>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>NDMS</h3>
      <p>National Digital Management System - A comprehensive digital identity solution for every stage of life in Sri Lanka. Secure, efficient, and future-ready.</p>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <p><a href="#home">Home</a></p>
      <p><a href="#about">About Us</a></p>
      <p><a href="#services">Services</a></p>
      <p><a href="#contact">Contact</a></p>
      <p><a href="login.php">Login Portal</a></p>
    </div>
    <div class="footer-section">
      <h3>Contact Information</h3>
      <p><i class="fas fa-envelope"></i> ndms@gov.lk</p>
      <p><i class="fas fa-phone"></i> +94 78 093 8755</p>
      <p><i class="fas fa-map-marker-alt"></i> Government Digital Hub<br>Galle, Sri Lanka</p>
    </div>
    <div class="footer-section">
      <h3>Office Hours</h3>
      <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
      <p>Saturday: 9:00 AM - 1:00 PM</p>
      <p>Sunday: Closed</p>
      <p>Emergency Support: 24/7</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2025 National Digital Management System (NDMS). All rights reserved. | Government of Sri Lanka</p>
  </div>
</footer>

<!-- Include AOS Library for Animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<script>
// Initialize AOS (Animate On Scroll)
AOS.init({
  duration: 1000,
  easing: 'ease-out-cubic',
  once: true,
  offset: 100
});

// Mobile Navigation Toggle
const menuToggle = document.getElementById('mobile-menu');
const navLinks = document.getElementById('nav-links');

menuToggle.addEventListener('click', () => {
  navLinks.classList.toggle('active');
});

// Close mobile menu when clicking on a link
navLinks.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', () => {
    navLinks.classList.remove('active');
  });
});

// Dark Mode Toggle
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
const body = document.body;

// Check for saved theme preference or default to 'light'
const currentTheme = localStorage.getItem('theme') || 'light';
body.setAttribute('data-theme', currentTheme);
updateThemeIcon(currentTheme);

themeToggle.addEventListener('click', () => {
  const currentTheme = body.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  
  body.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
  updateThemeIcon(newTheme);
  
  showToast(`Switched to ${newTheme} mode`, 'success');
});

function updateThemeIcon(theme) {
  themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Scroll Progress Bar
const scrollProgress = document.getElementById('scrollProgress');

window.addEventListener('scroll', () => {
  const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
  const progress = (window.pageYOffset / totalHeight) * 100;
  scrollProgress.style.width = progress + '%';
});

// Loading Screen
const loadingOverlay = document.getElementById('loadingOverlay');

window.addEventListener('load', () => {
  setTimeout(() => {
    loadingOverlay.classList.add('hidden');
  }, 1000);
});

// Enhanced Toast Notification
const toast = document.getElementById('toast');

function showToast(message, type = 'success') {
  // Clear any existing timeout
  if (toast.hideTimeout) {
    clearTimeout(toast.hideTimeout);
  }
  
  // Set toast content and styling
  toast.innerHTML = `
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <i class="fas ${getToastIcon(type)}"></i>
      <span>${message}</span>
    </div>
  `;
  
  toast.className = `toast ${type} show`;
  
  // Auto-hide after 5 seconds (8 seconds for info messages)
  const hideDelay = type === 'info' ? 8000 : 5000;
  toast.hideTimeout = setTimeout(() => {
    toast.classList.remove('show');
  }, hideDelay);
  
  // Allow manual dismissal by clicking
  toast.onclick = () => {
    toast.classList.remove('show');
    if (toast.hideTimeout) {
      clearTimeout(toast.hideTimeout);
    }
  };
}

function getToastIcon(type) {
  switch(type) {
    case 'success': return 'fa-check-circle';
    case 'error': return 'fa-exclamation-circle';
    case 'info': return 'fa-info-circle';
    case 'warning': return 'fa-exclamation-triangle';
    default: return 'fa-check-circle';
  }
}

// Testimonials Slider
const testimonialsSlider = document.getElementById('testimonialsSlider');
const navDots = document.querySelectorAll('.nav-dot');
let currentSlide = 0;
const totalSlides = 3;

navDots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    currentSlide = index;
    updateSlider();
  });
});

function updateSlider() {
  testimonialsSlider.style.transform = `translateX(-${currentSlide * 100}%)`;
  
  navDots.forEach((dot, index) => {
    dot.classList.toggle('active', index === currentSlide);
  });
}

// Auto-play testimonials
setInterval(() => {
  currentSlide = (currentSlide + 1) % totalSlides;
  updateSlider();
}, 5000);

// FAQ Accordion
const faqItems = document.querySelectorAll('.faq-item');

faqItems.forEach(item => {
  const question = item.querySelector('.faq-question');
  
  question.addEventListener('click', () => {
    const isActive = item.classList.contains('active');
    
    // Close all FAQ items
    faqItems.forEach(faqItem => {
      faqItem.classList.remove('active');
    });
    
    // Open clicked item if it wasn't active
    if (!isActive) {
      item.classList.add('active');
    }
  });
});

// Newsletter Form
const newsletterForm = document.getElementById('newsletterForm');
const newsletterResponse = document.getElementById('newsletter-response');

newsletterForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(newsletterForm);
  const email = formData.get('email');
  
  // Show loading state
  const submitBtn = newsletterForm.querySelector('.newsletter-btn');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
  submitBtn.disabled = true;
  
  try {
    const response = await fetch('./subscribe.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email: email })
    });
    
    const result = await response.json();
    
    if (result.success) {
      newsletterResponse.innerHTML = `
        <div style="color: white; background: rgba(16, 185, 129, 0.9); padding: 1rem; border-radius: 0.5rem; border: 1px solid #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
          <i class="fas fa-check-circle"></i> ${result.message}
        </div>
      `;
      newsletterForm.reset();
    } else {
      newsletterResponse.innerHTML = `
        <div style="color: white; background: rgba(239, 68, 68, 0.9); padding: 1rem; border-radius: 0.5rem; border: 1px solid #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
          <i class="fas fa-exclamation-circle"></i> ${result.message}
        </div>
      `;
    }
    
    // Clear message after 5 seconds
    setTimeout(() => {
      newsletterResponse.innerHTML = '';
    }, 5000);
    
  } catch (error) {
    newsletterResponse.innerHTML = `
      <div style="color: white; background: rgba(239, 68, 68, 0.9); padding: 1rem; border-radius: 0.5rem; border: 1px solid #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
        <i class="fas fa-exclamation-circle"></i> Network error. Please try again.
      </div>
    `;
    console.error('Newsletter subscription error:', error);
  } finally {
    // Restore button state
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
});

// Contact Form Enhanced Handler
const contactForm = document.querySelector('.contact-form');
const contactInputs = contactForm.querySelectorAll('input, textarea');
const contactSubmitBtn = contactForm.querySelector('button[type="submit"]');

// Add real-time validation
contactInputs.forEach(input => {
  input.addEventListener('input', validateField);
  input.addEventListener('blur', validateField);
});

function validateField(e) {
  const field = e.target;
  const value = field.value.trim();
  
  // Remove existing validation classes
  field.classList.remove('valid', 'invalid');
  
  // Remove existing error message
  const existingError = field.parentNode.querySelector('.field-error');
  if (existingError) {
    existingError.remove();
  }
  
  let isValid = true;
  let errorMessage = '';
  
  // Field-specific validation
  switch(field.name) {
    case 'name':
      if (value.length < 2) {
        isValid = false;
        errorMessage = 'Name must be at least 2 characters long';
      } else if (value.length > 100) {
        isValid = false;
        errorMessage = 'Name must not exceed 100 characters';
      } else if (!/^[a-zA-Z\s\.\-']+$/.test(value)) {
        isValid = false;
        errorMessage = 'Name contains invalid characters';
      }
      break;
      
    case 'email':
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
      } else if (value.length > 150) {
        isValid = false;
        errorMessage = 'Email address is too long';
      }
      break;
      
    case 'message':
      if (value.length < 10) {
        isValid = false;
        errorMessage = 'Message must be at least 10 characters long';
      } else if (value.length > 5000) {
        isValid = false;
        errorMessage = 'Message must not exceed 5000 characters';
      }
      break;
  }
  
  // Apply validation styling
  if (value && !isValid) {
    field.classList.add('invalid');
    showFieldError(field, errorMessage);
  } else if (value && isValid) {
    field.classList.add('valid');
  }
  
  return isValid;
}

function showFieldError(field, message) {
  const errorDiv = document.createElement('div');
  errorDiv.className = 'field-error';
  errorDiv.textContent = message;
  errorDiv.style.cssText = `
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
  `;
  errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
  field.parentNode.appendChild(errorDiv);
}

// Enhanced form submission
contactForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  // Validate all fields
  let isFormValid = true;
  contactInputs.forEach(input => {
    const fieldValid = validateField({ target: input });
    if (!fieldValid) isFormValid = false;
  });
  
  if (!isFormValid) {
    showToast('Please correct the errors in the form', 'error');
    return;
  }
  
  // Prepare form data
  const formData = new FormData(contactForm);
  formData.append('csrf_token', generateCSRFToken()); // Add CSRF protection
  
  // Show loading state
  const originalButtonContent = contactSubmitBtn.innerHTML;
  contactSubmitBtn.disabled = true;
  contactSubmitBtn.innerHTML = `
    <i class="fas fa-spinner fa-spin"></i>
    Sending Message...
  `;
  
  // Add loading animation to form
  contactForm.style.opacity = '0.7';
  contactForm.style.pointerEvents = 'none';
  
  try {
    const response = await fetch('./contact_handler.php', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Success handling
      contactForm.reset();
      contactInputs.forEach(input => {
        input.classList.remove('valid', 'invalid');
        const error = input.parentNode.querySelector('.field-error');
        if (error) error.remove();
      });
      
      showToast(result.message, 'success');
      
      // Show success animation
      showSuccessAnimation();
      
      // Reset button state immediately
      contactSubmitBtn.disabled = false;
      contactSubmitBtn.innerHTML = originalButtonContent;
      
      // Optional: Show reference number
      if (result.reference) {
        setTimeout(() => {
          showToast(`Reference ID: ${result.reference}`, 'info');
        }, 2000);
      }
      
    } else {
      // Error handling
      if (result.errors) {
        // Show field-specific errors
        Object.keys(result.errors).forEach(fieldName => {
          const field = contactForm.querySelector(`[name="${fieldName}"]`);
          if (field) {
            field.classList.add('invalid');
            showFieldError(field, result.errors[fieldName]);
          }
        });
      }
      
      showToast(result.message || 'An error occurred. Please try again.', 'error');
      
      // Handle rate limiting
      if (result.error_type === 'rate_limit') {
        contactSubmitBtn.disabled = true;
        contactSubmitBtn.classList.add('rate-limited');
        if (result.reset_time) {
          const resetTime = new Date(result.reset_time);
          const now = new Date();
          const timeUntilReset = Math.max(0, resetTime - now);
          
          setTimeout(() => {
            contactSubmitBtn.disabled = false;
            contactSubmitBtn.classList.remove('rate-limited');
            contactSubmitBtn.innerHTML = originalButtonContent;
          }, timeUntilReset);
        }
      }
    }
    
  } catch (error) {
    console.error('Contact form submission error:', error);
    showToast('Network error. Please check your connection and try again.', 'error');
  } finally {
    // Restore form state (only if not in a rate-limited state)
    contactForm.style.opacity = '1';
    contactForm.style.pointerEvents = 'auto';
    
    // Only reset button if it's not intentionally disabled (like for rate limiting)
    if (!contactSubmitBtn.classList.contains('rate-limited')) {
      contactSubmitBtn.disabled = false;
      contactSubmitBtn.innerHTML = originalButtonContent;
    }
  }
});

// Generate CSRF token for security
function generateCSRFToken() {
  // Return the server-generated CSRF token
  return '<?php echo $_SESSION['csrf_token']; ?>';
}

// Success animation
function showSuccessAnimation() {
  const successDiv = document.createElement('div');
  successDiv.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(16, 185, 129, 0.95);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    text-align: center;
    z-index: 10000;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    animation: successPulse 0.6s ease-out;
  `;
  
  successDiv.innerHTML = `
    <div style="font-size: 3rem; margin-bottom: 1rem;">
      <i class="fas fa-check-circle"></i>
    </div>
    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;">Message Sent!</h3>
    <p style="margin: 0; opacity: 0.9;">Thank you for contacting us.</p>
  `;
  
  document.body.appendChild(successDiv);
  
  setTimeout(() => {
    successDiv.style.animation = 'successFadeOut 0.5s ease-in forwards';
    setTimeout(() => {
      document.body.removeChild(successDiv);
    }, 500);
  }, 2000);
}

// Add CSS for form validation and animations
const contactFormStyles = document.createElement('style');
contactFormStyles.textContent = `
  @keyframes successPulse {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
    50% { transform: translate(-50%, -50%) scale(1.05); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  }
  
  @keyframes successFadeOut {
    to { transform: translate(-50%, -50%) scale(0.9); opacity: 0; }
  }
  
  .contact-form input.valid,
  .contact-form textarea.valid {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }
  
  .contact-form input.invalid,
  .contact-form textarea.invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
  }
  
  .contact-form input.valid::after {
    content: '✓';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #10b981;
    font-weight: bold;
  }
  
  .field-error {
    animation: errorSlideIn 0.3s ease-out;
  }
  
  @keyframes errorSlideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .toast.info {
    border-left: 4px solid #3b82f6;
    background: rgba(59, 130, 246, 0.1);
  }
  
  .contact-form button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
  }
  
  .contact-form button:disabled:hover {
    transform: none !important;
    box-shadow: var(--shadow-md) !important;
  }
`;
document.head.appendChild(contactFormStyles);

// Smooth Scrolling for Navigation Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// Stats Counter Animation
const statsNumbers = document.querySelectorAll('.stat-number');
const statsSection = document.querySelector('.stats-section');

const animateStats = () => {
  statsNumbers.forEach(stat => {
    const target = parseInt(stat.textContent.replace(/[^\d]/g, ''));
    const suffix = stat.textContent.replace(/[\d.]/g, '');
    let current = 0;
    const increment = target / 100;
    
    const updateCount = () => {
      if (current < target) {
        current += increment;
        stat.textContent = Math.floor(current) + suffix;
        requestAnimationFrame(updateCount);
      } else {
        stat.textContent = target + suffix;
      }
    };
    
    updateCount();
  });
};

// Intersection Observer for Stats Animation
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateStats();
      observer.unobserve(entry.target);
    }
  });
});

if (statsSection) {
  observer.observe(statsSection);
}

// Add parallax effect to hero section
window.addEventListener('scroll', () => {
  const scrolled = window.pageYOffset;
  const hero = document.querySelector('.hero');
  const heroVideo = hero.querySelector('video');
  
  if (heroVideo) {
    heroVideo.style.transform = `translateY(${scrolled * 0.5}px)`;
  }
});

// Add typing effect to hero title
const typingText = document.querySelector('.typing-text');
if (typingText) {
  const text = typingText.textContent;
  typingText.textContent = '';
  
  let i = 0;
  const typeWriter = () => {
    if (i < text.length) {
      typingText.textContent += text.charAt(i);
      i++;
      setTimeout(typeWriter, 100);
    }
  };
  
  setTimeout(typeWriter, 1000);
}

// Add hover effects to service cards
const serviceCards = document.querySelectorAll('.service-card');
serviceCards.forEach(card => {
  card.addEventListener('mouseenter', () => {
    card.style.transform = 'translateY(-8px) rotate(1deg)';
  });
  
  card.addEventListener('mouseleave', () => {
    card.style.transform = 'translateY(0) rotate(0)';
  });
});

// Add click effect to CTA buttons
const ctaButtons = document.querySelectorAll('.cta-primary, .cta-secondary');
ctaButtons.forEach(button => {
  button.addEventListener('click', (e) => {
    // Create ripple effect
    const ripple = document.createElement('div');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
      position: absolute;
      width: ${size}px;
      height: ${size}px;
      left: ${x}px;
      top: ${y}px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      transform: scale(0);
      animation: ripple 0.6s linear;
      pointer-events: none;
    `;
    
    button.style.position = 'relative';
    button.style.overflow = 'hidden';
    button.appendChild(ripple);
    
    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
});

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

console.log('NDMS Homepage Enhanced with Advanced Animations and Interactions! 🚀');
</script>

</body>
</html>
