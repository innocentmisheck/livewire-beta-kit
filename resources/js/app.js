import Chart from 'chart.js/auto';
window.Chart = Chart;

    function showLoadingSpinner() {
        document.getElementById('loading-spinner').style.display = 'flex';  // Show spinner
        document.getElementById('content').style.display = 'none';         // Hide content
    }

    setTimeout(function() {
        document.getElementById('loading-spinner').style.display = 'none'; // Hide spinner after 5 seconds
        document.getElementById('content').style.display = 'block';        // Show content
    }, 5000);  // Adjust time as needed

