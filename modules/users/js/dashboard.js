// modules/users/js/dashboard.js — Chart initialization cho Dashboard tab
// Biến chart1Labels, chart1Data, chart2Labels, chart2Data được define inline trong index.php

(function() {
    if (typeof chart1Labels === 'undefined') return;

    const chart2DataMillion = chart2Data.map(val => (val / 1000000).toFixed(2));

    const ctxQty = document.getElementById('quantityChart').getContext('2d');
    new Chart(ctxQty, {
        type: 'bar',
        data: {
            labels: chart1Labels,
            datasets: [{
                label: 'Số lượng sản phẩm',
                data: chart1Data,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    const ctxVal = document.getElementById('valueChart').getContext('2d');
    new Chart(ctxVal, {
        type: 'doughnut',
        data: {
            labels: chart2Labels,
            datasets: [{
                label: 'Giá trị (Triệu VNĐ)',
                data: chart2DataMillion,
                backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        font: { family: "'Inter', sans-serif" }
                    }
                }
            }
        }
    });
})();
