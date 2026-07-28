// modules/users/js/dashboard.js — Chart initialization cho Dashboard tab

(function() {
    if (typeof chart1Labels === 'undefined') return;

    Chart.register(ChartDataLabels);

    const chartFont = { family: "'Inter', sans-serif" };
    const dlFont = { family: "'Inter', sans-serif", weight: '600' };

    // ── Biểu đồ 1: Số lượng theo danh mục ──────────────────────────────
    const ctxQty = document.getElementById('quantityChart');
    if (ctxQty) {
        const max1 = Math.max(...chart1Data, 0);
        new Chart(ctxQty.getContext('2d'), {
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
                clip: false,
                layout: { padding: { top: 20 } },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#1e293b',
                        font: { ...dlFont, size: 11 },
                        offset: 2,
                        formatter: val => val.toLocaleString('vi-VN')
                    }
                },
                scales: {
                    y: { beginAtZero: true, max: max1 + Math.ceil(max1 * 0.15), grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false }, ticks: { font: chartFont } }
                }
            }
        });
    }

    // ── Biểu đồ 2: Giá trị theo danh mục (donut) ──────────────────────
    const ctxVal = document.getElementById('valueChart');
    if (ctxVal) {
        const chart2DataMillion = chart2Data.map(val => (val / 1000).toFixed(2));
        const total2 = chart2DataMillion.reduce((a, b) => a + parseFloat(b), 0);
        new Chart(ctxVal.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chart2Labels,
                datasets: [{
                    label: 'Giá trị (Nghìn VNĐ)',
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
                        labels: { boxWidth: 12, font: chartFont }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { ...dlFont, size: 11 },
                        display: function(ctx) {
                            const val = parseFloat(ctx.dataset.data[ctx.dataIndex]);
                            return val / total2 >= 0.05;
                        },
                        formatter: (val, ctx) => {
                            const pct = total2 > 0 ? ((parseFloat(val) / total2) * 100).toFixed(1) : 0;
                            return pct + '%';
                        }
                    }
                }
            }
        });
    }

    // ── Biểu đồ 3: Xu hướng nhập/xuất 6 tháng ─────────────────────────
    const ctxTrend = document.getElementById('trendChart');
    if (ctxTrend && typeof chartTrendLabels !== 'undefined') {
        const allTrendData = [...chartTrendImport, ...chartTrendExport].filter(v => v != null);
        const maxTrend = Math.max(...allTrendData, 0);
        new Chart(ctxTrend.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartTrendLabels,
                datasets: [
                    {
                        label: 'Phiếu nhập',
                        data: chartTrendImport,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981'
                    },
                    {
                        label: 'Phiếu xuất',
                        data: chartTrendExport,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#f59e0b'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                clip: false,
                layout: { padding: { top: 20 } },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12, font: chartFont, usePointStyle: true }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#334155',
                        font: { ...dlFont, size: 10 },
                        offset: 2,
                        formatter: val => Number.isInteger(val) ? val : val.toFixed(1)
                    }
                },
                scales: {
                    y: { beginAtZero: true, max: maxTrend + Math.ceil(maxTrend * 0.2) || 5, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false }, ticks: { font: chartFont } }
                }
            }
        });
    }

    // ── Biểu đồ 4: Trạng thái kho (donut) ──────────────────────────────
    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus && typeof chartStatusLabels !== 'undefined') {
        const total4 = chartStatusData.reduce((a, b) => a + b, 0);
        new Chart(ctxStatus.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartStatusLabels,
                datasets: [{
                    data: chartStatusData,
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: chartFont, padding: 16 }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { ...dlFont, size: 11 },
                        display: function(ctx) {
                            return ctx.dataset.data[ctx.dataIndex] > 0;
                        },
                        formatter: (val, ctx) => {
                            const pct = total4 > 0 ? ((val / total4) * 100).toFixed(1) : 0;
                            return val + '\n(' + pct + '%)';
                        }
                    }
                }
            }
        });
    }

    // ── Biểu đồ 5: Top 5 bán chạy (horizontal bar) ─────────────────────
    const ctxTop = document.getElementById('topSellingChart');
    if (ctxTop && typeof chartTopLabels !== 'undefined') {
        const max5 = Math.max(...chartTopData, 0);
        new Chart(ctxTop.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartTopLabels,
                datasets: [{
                    label: 'Số lượng bán',
                    data: chartTopData,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                clip: false,
                layout: { padding: { right: 30 } },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#1e293b',
                        font: { ...dlFont, size: 12 },
                        offset: 4,
                        formatter: val => val.toLocaleString('vi-VN')
                    }
                },
                scales: {
                    x: { beginAtZero: true, max: max5 + Math.ceil(max5 * 0.15) || 5, grid: { color: '#f1f5f9' } },
                    y: { grid: { display: false }, ticks: { font: { ...chartFont, size: 12 } } }
                }
            }
        });
    }
})();
