<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TRACKU - Student Bus Tracking System</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body, html { font-family: 'Helvetica', sans-serif; height: 100%; color: #fff; }

   
    body {
        background: url('https://www.skoolbeep.com/blog/wp-content/uploads/2020/12/HOW-DO-TRACKING-APPS-HELP-MANAGE-SCHOOL-BUSES-1536x787.png') no-repeat center center fixed;
        background-size: cover;
        position: relative;
    }
    body::after {
        content: '';
        position: absolute;
        top:0; left:0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 50, 0.6);
        z-index: 0;
    }

    .hero {
        position: relative;
        z-index: 1;
        height: 85vh;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        text-align: center;
        padding: 100px 20px 0;
    }

    h1 {
        font-size: 4rem;
        font-weight: 700;
        margin-bottom: 25px;
        color: #ffffff;
        text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
    }

    .tagline {
        font-size: 1.6rem;
        margin-bottom: 40px; 
        font-style: italic;
        color: #FFD700;
        text-shadow: 1px 1px 5px rgba(0,0,0,0.7);
    }

    p {
        font-size: 1.2rem;
        margin-bottom: 60px; 
        max-width: 600px;
        color: #f0f0f0;
        text-shadow: 1px 1px 5px rgba(0,0,0,0.6);
    }

    .btn {
        background-color: #0072ff;
        color: #fff;
        padding: 18px 55px;
        font-size: 1.2rem;
        font-weight: bold;
        border-radius: 30px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-top: 40px; 
    }
    .btn:hover {
        background-color: #005fcc;
    }

    .footer {
        position: relative;
        z-index: 1;
        height: 15vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .icon-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 35px;
    }
    .icon-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .icon {
        width: 45px;
        height: 45px;
        fill: #ffffff;
        transition: transform 0.3s ease, fill 0.3s ease;
        filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.6));
    }
    .icon:hover {
        transform: scale(1.15);
        fill: #FFD700;
        filter: drop-shadow(3px 3px 6px rgba(0,0,0,0.7));
    }
    .icon-block span {
        font-size: 0.85rem;
        color: #ffffff;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
    }

    @media (max-width:768px){
        h1 { font-size: 2.5rem; }
        .tagline { font-size: 1.2rem; margin-bottom: 25px; }
        p { font-size: 1rem; margin-bottom: 40px; }
        .btn { padding: 12px 35px; font-size: 1rem; }
        .icon-container { flex-direction: row; flex-wrap: wrap; gap: 25px; }
        .icon { width: 40px; height: 40px; }
    }
</style>
</head>
<body>
    <div class="hero">
        <h1>TRACKU</h1>
        <div class="tagline">Track your ride, ace your day!</div>
        <p>On time, every time: No more waiting games at the bus stop. Get real-time peace of mind with TRACKU!</p>
        <a href="login.php" class="btn">Login to TRACKU</a>
    </div>

    
    <div class="footer">
        <div class="icon-container">
            
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <rect x="8" y="16" width="48" height="32" rx="4" fill="currentColor"/>
                    <circle cx="16" cy="52" r="4" fill="#fff"/>
                    <circle cx="48" cy="52" r="4" fill="#fff"/>
                </svg>
                <span>Buses</span>
            </div>

           
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <path d="M32 2C21 2 12 11 12 22c0 11 20 40 20 40s20-29 20-40C52 11 43 2 32 2zM32 32c-5.5 0-10-4.5-10-10s4.5-10 10-10 10 4.5 10 10-4.5 10-10 10z" fill="currentColor"/>
                </svg>
                <span>Tracking</span>
            </div>

       
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <circle cx="32" cy="32" r="30" fill="currentColor"/>
                    <line x1="32" y1="32" x2="32" y2="16" stroke="#fff" stroke-width="4"/>
                    <line x1="32" y1="32" x2="44" y2="32" stroke="#fff" stroke-width="4"/>
                </svg>
                <span>On Time</span>
            </div>

         
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <circle cx="32" cy="20" r="12" fill="currentColor"/>
                    <path d="M16 56c0-10 8-18 16-18s16 8 16 18H16z" fill="currentColor"/>
                </svg>
                <span>Students</span>
            </div>

      
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <circle cx="20" cy="22" r="10" fill="currentColor"/>
                    <circle cx="44" cy="22" r="10" fill="currentColor"/>
                    <path d="M8 56c0-12 8-18 12-18s12 6 12 18H8zM32 56c0-12 8-18 12-18s12 6 12 18H32z" fill="currentColor"/>
                </svg>
                <span>Parents</span>
            </div>

         
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <circle cx="32" cy="20" r="10" fill="currentColor"/>
                    <path d="M16 56c0-8 6-14 16-14s16 6 16 14H16z" fill="currentColor"/>
                </svg>
                <span>Drivers</span>
            </div>

            
            <div class="icon-block">
                <svg class="icon" viewBox="0 0 64 64">
                    <path d="M32 60a6 6 0 006-6H26a6 6 0 006 6zm18-18V30c0-9-5-16-14-18V10a4 4 0 10-8 0v2c-9 2-14 9-14 18v12l-6 6v2h48v-2l-6-6z" fill="currentColor"/>
                </svg>
                <span>Alerts</span>
            </div>
        </div>
    </div>
</body>
</html>
