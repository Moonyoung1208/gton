<nav class="admin-nav" id="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-title font-godo">관리자 메뉴</span>
        <i class="fa-solid fa-xmark" id="menuClose"></i>
    </div>

    <ul class="admin-nav-list">
        <li class="admin-nav-item">
            <a href="admin-dashboard.html" class="admin-nav-link">
                <i class="fa-solid fa-chart-line"></i> 대시보드
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="admin-employees.html" class="admin-nav-link">
                <i class="fa-solid fa-users"></i> 직원관리
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="admin-departments.php" class="admin-nav-link">
                <i class="fa-solid fa-sitemap"></i> 부서관리
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="admin-records.html" class="admin-nav-link">
                <i class="fa-solid fa-calendar-days"></i> 기록관리
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="admin-holidays.html" class="admin-nav-link">
                <i class="fa-regular fa-calendar-xmark"></i> 휴일관리
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="admin-settings.html" class="admin-nav-link">
                <i class="fa-solid fa-gear"></i> 전체설정
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="#" class="logout-btn" onclick="confirmLogout(event)">
            <i class="fa-solid fa-right-from-bracket"></i> 로그아웃
        </a>
    </div>
</nav>

<div class="sidebar-overlay" id="overlay"></div>