<aside class="sidebar">
    <div class="logo">
        <span>Sinapp Social</span>
    </div>
    <nav>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="/dashboard" class="nav-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="/pricing" class="nav-link <?php echo ($active_page ?? '') === 'pricing' ? 'active' : ''; ?>">
                    <i class="fas fa-gem"></i> Subscription Plans
                </a>
            </li>

            <li class="nav-header">X AUTOMATION</li>

            <li class="nav-item">
                <a href="/accounts" class="nav-link <?php echo ($active_page ?? '') === 'accounts' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Connected Accounts
                </a>
            </li>
            <li class="nav-item">
                <a href="/routines"
                    class="nav-link <?php echo ($active_page ?? '') === 'routines_index' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i> AI Post Planner
                </a>
            </li>
            <li class="nav-item">
                <a href="/routines/create"
                    class="nav-link <?php echo ($active_page ?? '') === 'routines_create' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> New AI Plan
                </a>
            </li>
            <li class="nav-item">
                <a href="/targets" class="nav-link <?php echo $active_page === 'targets' ? 'active' : ''; ?>">
                    <i class="fas fa-reply-all"></i> Auto-Reply Manager
                </a>
            </li>
            <li class="nav-item">
                <a href="/xml-sources"
                    class="nav-link <?php echo ($active_page ?? '') === 'xml_sources' ? 'active' : ''; ?>">
                    <i class="fas fa-rss"></i> XML Data Sources
                </a>
            </li>
            <li class="nav-item">
                <a href="/posts" class="nav-link <?php echo $active_page === 'posts' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Scheduled Posts
                </a>
            </li>
            <li class="nav-item">
                <a href="/logs" class="nav-link <?php echo $active_page === 'logs' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Activity Logs
                </a>
            </li>
            <?php
            $currentUser = \App\Models\User::findById($_SESSION['user_id']);
            if ($currentUser && \App\Models\User::isAdmin($currentUser)):
                ?>
                <li class="nav-item"
                    style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <a href="/admin" class="nav-link <?php echo $active_page === 'admin' ? 'active' : ''; ?>"
                        style="color: var(--accent-color);">
                        <i class="fas fa-user-shield"></i> Admin Panel
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer" style="margin-top: auto; padding-top: 2rem; border-top: 1px solid var(--border-color);">
        <button id="theme-toggle" class="nav-link"
            style="width: 100%; justify-content: flex-start; background: none; cursor: pointer;">
            <i class="fas fa-sun"></i> Theme Mode
        </button>
        <a href="/logout" class="nav-link" style="color: var(--error);">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<script>
    document.querySelectorAll('.nav-dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdown = toggle.closest('.nav-dropdown');
            dropdown.classList.toggle('active');
        });
    });
</script>