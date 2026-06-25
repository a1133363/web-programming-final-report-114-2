document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.primary-nav');
    navToggle?.addEventListener('click', () => {
        const open = nav?.classList.toggle('is-open') ?? false;
        navToggle.setAttribute('aria-expanded', String(open));
    });

    document.querySelectorAll('[data-countdown]').forEach((element) => {
        const output = element.querySelector('strong');
        const target = new Date(element.dataset.countdown).getTime();
        const update = () => {
            const difference = target - Date.now();
            if (!output) return;
            if (difference <= 0) {
                output.textContent = '已截標';
                element.classList.add('is-ended');
                element.classList.remove('is-urgent');
                return;
            }
            if (difference > 0 && difference <= 3600000) {
                element.classList.add('is-urgent');
            } else {
                element.classList.remove('is-urgent');
            }
            const days = Math.floor(difference / 86400000);
            const hours = Math.floor((difference % 86400000) / 3600000);
            const minutes = Math.floor((difference % 3600000) / 60000);
            const seconds = Math.floor((difference % 60000) / 1000);
            output.textContent = `${days ? `${String(days).padStart(2, '0')}D ` : ''}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        };
        update();
        setInterval(update, 1000);
    });

    document.querySelector('[data-dismiss-toast]')?.addEventListener('click', (event) => {
        event.currentTarget.closest('.toast-wrap')?.remove();
    });

    document.querySelectorAll('[data-demo-email]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('.auth-card')?.querySelector('form');
            if (!form) return;
            form.querySelector('[name="email"]').value = button.dataset.demoEmail;
            form.querySelector('[name="password"]').value = 'demo1234';
            form.querySelector('[name="password"]').focus();
        });
    });

    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(button.dataset.dialogOpen)?.showModal());
    });
    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelector('[data-ai-generate]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const form = button.closest('form');
        const keywords = form?.querySelector('[name="ai_keywords"]')?.value.trim();
        if (!form || !keywords) {
            form?.querySelector('[name="ai_keywords"]')?.focus();
            return;
        }
        const original = button.textContent;
        button.disabled = true;
        button.textContent = '生成中…';
        try {
            const payload = new FormData();
            payload.append('_csrf', form.querySelector('[name="_csrf"]').value);
            payload.append('keywords', keywords);
            const response = await fetch(button.dataset.endpoint, { method: 'POST', body: payload });
            const data = await response.json();
            if (!response.ok || data.error) throw new Error(data.error || '無法產生描述');
            form.querySelector('[name="description"]').value = data.description;
            form.querySelector('[name="description"]').focus();
        } catch (error) {
            window.alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = original;
        }
    });

    if (window.Chart) {
        Chart.defaults.color = '#bdb3a2';
        Chart.defaults.borderColor = 'rgba(241,234,219,.1)';
        Chart.defaults.font.family = 'Cascadia Mono, Consolas, monospace';
        const volume = document.getElementById('volumeChart');
        if (volume) {
            const values = JSON.parse(volume.dataset.values || '[]');
            new Chart(volume, {
                type: 'line',
                data: { labels: ['06/14','06/15','06/16','06/17','06/18','06/19','06/20'], datasets: [{ data: values, borderColor: '#8c6ab8', backgroundColor: 'rgba(140,106,184,.14)', fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: '#c0966d' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: (value) => `${value / 1000}k` } }, x: { grid: { display: false } } } }
            });
        }
        const category = document.getElementById('categoryChart');
        if (category) {
            const values = JSON.parse(category.dataset.values || '[]');
            new Chart(category, {
                type: 'doughnut',
                data: { labels: ['遺物','科技','情報','文獻'], datasets: [{ data: values, backgroundColor: ['#c0966d','#8c6ab8','#6fa887','#d05b62'], borderWidth: 0, spacing: 3 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 9, usePointStyle: true, padding: 16 } } } }
            });
        }
    }

    // 後台輕量級 Tab 切換邏輯 (買家與管理員後台)
    const sidebar = document.querySelector('.dashboard-sidebar, .admin-sidebar');
    if (sidebar) {
        const navLinks = sidebar.querySelectorAll('nav a');
        const contentContainer = document.querySelector('.dashboard-content, .admin-content');
        
        if (contentContainer) {
            const heading = contentContainer.querySelector('.dashboard-heading, .admin-topbar');
            const metrics = contentContainer.querySelector('.metric-grid');
            const sections = contentContainer.querySelectorAll('.dashboard-panel, .admin-chart-grid, .wanted-admin, .review-panel');
            
            const switchTab = (targetHash) => {
                const cleanHash = targetHash || '#overview';
                
                // 1. 更新側邊欄 active 樣式
                navLinks.forEach((link) => {
                    const href = link.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        if (href === cleanHash) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    }
                });
                
                // 2. 切換各區塊的顯示/隱藏
                if (cleanHash === '#overview') {
                    if (heading) heading.style.display = '';
                    if (metrics) metrics.style.display = '';
                    sections.forEach(sec => sec.style.display = '');
                } else {
                    if (heading) heading.style.display = 'none';
                    if (metrics) metrics.style.display = 'none';
                    
                    sections.forEach((sec) => {
                        const id = sec.getAttribute('id');
                        if (id && `#${id}` === cleanHash) {
                            sec.style.display = '';
                        } else {
                            sec.style.display = 'none';
                        }
                    });
                }
            };
            
            // 點擊事件綁定
            navLinks.forEach((link) => {
                const href = link.getAttribute('href');
                if (href && href.startsWith('#')) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        history.pushState(null, '', href);
                        switchTab(href);
                    });
                }
            });
            
            // 監聽上一頁下一頁
            window.addEventListener('popstate', () => {
                switchTab(window.location.hash);
            });
            
            // 初始化選取狀態
            if (window.location.hash) {
                switchTab(window.location.hash);
            } else {
                switchTab('#overview');
            }
        }
    }
});
