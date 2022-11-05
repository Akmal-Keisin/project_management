
<!-- ============================================================== -->
<!-- Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->
<aside class="left-sidebar" data-sidebarbg="skin6">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar" data-sidebarbg="skin6">
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li class="sidebar-item {{ (Request::is('dashboard*') ? 'selected' : '') }}"> <a class="sidebar-link sidebar-link" href="{{ url('dashboard') }}"
                        aria-expanded="false"><i data-feather="home" class="feather-icon"></i><span
                            class="hide-menu">Dashboard</span></a></li>
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Applications</span></li>

                <li class="sidebar-item {{ (Request::is('project*') ? 'selected' : '') }}"> <a class="sidebar-link" href="{{ url('/project') }}"
                        aria-expanded="false"><i class="fas fa-sitemap"></i><span
                            class="hide-menu">Projects
                        </span></a>
                </li>
                <li class="sidebar-item {{ (Request::is('user*') ? 'selected' : '') }}"> <a class="sidebar-link sidebar-link" href="{{ url('user') }}"
                        aria-expanded="false"><i class="fas fa-users"></i><span
                            class="hide-menu">Users</span></a></li>
                <li class="sidebar-item {{ (Request::is('admin*') ? 'selected' : '') }}"> <a class="sidebar-link sidebar-link" href="{{ url('admin') }}"
                        aria-expanded="false"><i class="fas fa-microchip"></i><span
                            class="hide-menu">Admin</span></a></li>
                </li>
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
<!-- ============================================================== -->
<!-- End Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->