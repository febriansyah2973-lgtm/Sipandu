document.addEventListener("DOMContentLoaded", function() {
    const counters = document.querySelectorAll('.count-up');
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const inc = target / 50; 

            if (count < target) {
                counter.innerText = Math.ceil(count + inc);
                setTimeout(updateCount, 20);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });
    const patroliHariIni = parseInt(document.getElementById('data-patroli').value) || 0;
    const totalInsiden = parseInt(document.getElementById('data-insiden').value) || 0;
    const ctx1 = document.getElementById('patrolChart');
    if (ctx1) {
        const chartCtx1 = ctx1.getContext('2d');
        let gradient = chartCtx1.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)'); 
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(chartCtx1, {
            type: 'line',
            data: {
                labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                datasets: [{
                    label: 'Scan',
                    data: [12, 19, 15, 25, 22, 30, patroliHariIni], 
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    const ctx2 = document.getElementById('statusChart');
    if (ctx2) {
        let aman = patroliHariIni - totalInsiden;
        if(aman < 0) aman = 0;

        new Chart(ctx2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Aman', 'Insiden'],
                datasets: [{
                    data: [aman, totalInsiden],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    }
});