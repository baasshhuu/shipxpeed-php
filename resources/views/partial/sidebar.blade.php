<nav id="sidebar">
    <ul class="list-unstyled menu-categories ps ps--active-y" id="accordionExample">
        <li class="sx-menu-heading">Overview</li>
        <li class="menu @routeis('dashboard') active @endrouteis">
            <a href="{{ route('dashboard') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-duotone fa-house"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>
        <li class="sx-menu-heading">Sellers</li>
        <li class="menu @routeis('seller-list') active @endrouteis">
            <a href="{{ route('seller-list') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>
                    <span>All Sellers</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('negative-balance.index') active @endrouteis">
            <a href="{{ route('negative-balance.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-scale-unbalanced"></i>
                    <span>Negative Balance</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('get.rto.page') active @endrouteis">
            <a href="{{ route('get.rto.page') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>RTO Charges</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('invoices.add') active @endrouteis">
            <a href="{{ route('invoices.add') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span>Seller Invoices</span>
                </div>
            </a>
        </li>
        <li class="sx-menu-heading">Orders &amp; Shipments</li>
        <li class="menu @routeis('get.orders') active @endrouteis">
            <a href="{{ route('get.orders') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-box"></i>
                    <span>Order Status</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('shipment.report') active @endrouteis">
            <a href="{{ route('shipment.report') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                    <span>Shipment Report</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('order.status.upload.form') active @endrouteis">
            <a href="{{ route('order.status.upload.form') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    <span>Bulk Status Upload</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('weight.dispatching.index') active @endrouteis">
            <a href="{{ route('weight.dispatching.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-weight-scale"></i>
                    <span>Weight Disputes</span>
                </div>
            </a>
        </li>
        <li class="sx-menu-heading">Couriers &amp; Rates</li>
        <li class="menu @routeis('logistics') active @endrouteis">
            <a href="{{ route('logistics') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-warehouse"></i>
                    <span>Logistics Partners</span>
                </div>
            </a>
        </li>
        @if (Route::has('admin.fship.index'))
        <li class="menu @routeis('admin.fship.index') active @endrouteis">
            <a href="{{ route('admin.fship.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-plug"></i>
                    <span>Fship Integration</span>
                </div>
            </a>
        </li>
        @endif
        <li class="menu @routeis('rate.manager') active @endrouteis">
            <a href="{{ route('rate.manager') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Rate Manager</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('active.slebs') active @endrouteis">
            <a href="{{ route('active.slebs.add') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Courier Status</span>
                </div>
            </a>
        </li>
        <li class="sx-menu-heading">Finance</li>
        <li class="menu @routeis('codremittance.index') active @endrouteis">
            <a href="{{ route('codremittance.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                    <span>COD Remittance</span>
                </div>
            </a>
        </li>
        @if(Helper::userCan([107]))
        <li class="menu @routeis('roles') active @endrouteis">
            <a href="#wallet" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('roles') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Transaction</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('Recharge') show @endrouteis" id="wallet"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(107))
                <li class="@routeis('recharges') active @endrouteis">
                    <a href="{{ route('recharges') }}">Recharge</a>
                </li>
                @endif


              @if(Helper::userCan(107))
                <li class="@routeis('balance') active @endrouteis">
                    <a href="{{ route('balance') }}">Balance</a>
                </li>
                @endif

            </ul>
        </li>
        @endif
        <li class="sx-menu-heading">Support</li>
        @if(Helper::userCan([102,103]))
        <li class="menu @routeis('roles,users') active @endrouteis">
            <a href="#ticket" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('roles,users') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-ticket"></i>
                    <span>Tickets</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('roles,users') show @endrouteis" id="ticket"
                data-bs-parent="#accordionExample">


                <li class="@routeis('ticket') active @endrouteis">
                    <a href="{{ route('ticket') }}">Tickets</a>
                </li>
                {{-- @if(Helper::userCan(102))
                <li class="@routeis('roles') active @endrouteis">
                    <a href="{{ route('roles') }}">Tickets</a>
                </li>
                @endif
                @if(Helper::userCan(103))
                <li class="@routeis('users') active @endrouteis">
                    <a href="{{ route('users') }}">Ticket Category</a>
                </li>
                @endif --}}
            </ul>
        </li>
        @endif
        @if(Helper::userCan([102,103]))
        <li class="menu @routeis('get-in-touch,inquiries') active @endrouteis">
            <a href="#forms-detail" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('get-in-touch,inquiries') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-clipboard"></i>
                    <span>Forms Detail</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('get-in-touch,inquiries') show @endrouteis" id="forms-detail"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(102))
                <li class="@routeis('get-in-touch') active @endrouteis">
                    <a href="{{ route('get-in-touch') }}">Get In Touch</a>
                </li>
                @endif
                @if(Helper::userCan(102))
                <li class="@routeis('inquiries') active @endrouteis">
                    <a href="{{ route('inquiries') }}">Inquiries</a>
                </li>
                @endif 
                @if(Helper::userCan(102))
                @endif

            </ul>
        </li>
        @endif
        <li class="sx-menu-heading">System</li>
        @if(Helper::userCan([102,103]))
        <li class="menu @routeis('roles,users') active @endrouteis">
            <a href="#master" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('roles,users') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>Master</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('roles,users') show @endrouteis" id="master"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(102))
                <li class="@routeis('roles') active @endrouteis">
                    <a href="{{ route('roles') }}">Roles</a>
                </li>
                @endif
                @if(Helper::userCan(103))
                <li class="@routeis('users') active @endrouteis">
                    <a href="{{ route('users') }}">Sub Admins</a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @if(Helper::userCan(101))
        <li class="menu @routeis('setting') active @endrouteis">
            <a href="#setting" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('setting') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa fa-cog my-auto"></i>
                    <span>App Setting</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('setting') show @endrouteis" id="setting"
                data-bs-parent="#accordionExample">
                @foreach(config('constant.setting_array', []) as $key => $setting)
                <li class="@if(request()->path() == 'setting/'.$key) active @endif">
                    <a class="nav-link" href="{{ route('setting', ['id' => $key]) }}">
                        {{ $setting }}
                    </a>
                </li>
                @endforeach
            </ul>
        </li>

        <li class="menu">
            <a href="{{route('database_backup')}}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-duotone fa-database"></i>
                    <span>Database Backup</span>
                </div>
            </a>
        </li>

        <li class="menu  @routeis('server-control') active @endrouteis">
            <a href="{{ route('server-control') }}" aria-expanded="false" class="dropdown-toggle">
                <div class="">
                    <i class="fa-duotone fa-server"></i>
                    <span>Server Control Panel</span>
                </div>
            </a>
        </li>
        @endif
    </ul>
</nav>
