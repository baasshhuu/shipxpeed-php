<nav id="sidebar">
    <div class="shadow-bottom"></div>
    <ul class="list-unstyled menu-categories ps ps--active-y" id="accordionExample">
        <li class="menu @routeis('dashboard') active @endrouteis">
            <a href="{{ route('dashboard') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-duotone fa-house"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>



    <li class="menu @routeis('order.status.upload.form') active @endrouteis">
            <a href="{{ route('order.status.upload.form') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>

                    <span>Order Status Upload</span>
                </div>
            </a>
        </li>



    <li class="menu @routeis('SendWhatsApp.index') active @endrouteis">
            <a href="{{ route('SendWhatsApp.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>

                    <span>SendWhatsApp Messages</span>
                </div>
            </a>
        </li>


        <li class="menu @routeis('seller-list') active @endrouteis">
            <a href="{{ route('seller-list') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>

                    <span>Seller List</span>
                </div>
            </a>
        </li>


  <li class="menu @routeis('get.orders') active @endrouteis">
            <a href="{{ route('get.orders') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>

                    <span>Order Status</span>
                </div>
            </a>
        </li>



          <li class="menu @routeis('get.rto.page') active @endrouteis">
            <a href="{{ route('get.rto.page') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>

                    <span>RTO Amount</span>
                </div>
            </a>
        </li>


         <li class="menu @routeis('invoices.add') active @endrouteis">
            <a href="{{ route('invoices.add') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>

                    <span>Seller Invoice</span>
                </div>
            </a>
        </li>

        
        <li class="menu @routeis('logistics') active @endrouteis">
            <a href="{{ route('logistics') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-tags"></i>
                    <span>Logistics</span>
                </div>
            </a>
        </li>

        
        <li class="menu @routeis('pricesetting') active @endrouteis">
            <a href="{{ route('pricesetting.add') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-tags"></i>
                    <span>Price Setting</span>
                </div>
            </a>
        </li>


        <li class="menu @routeis('zone.price.index') active @endrouteis">
            <a href="{{ route('zone.price.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-tags"></i>
                    <span>Zone Price Setting</span>
                </div>
            </a>
        </li>
<li>
<a href="{{ route('courier.rate.manager') }}" class="dropdown-toggle">
<div class="">
<i class="fa-solid fa-truck-fast"></i>
<span>Courier &amp; Rate Manager</span>
</div>
</a>
</li>
<li>
<a href="{{ route('courier.master.index') }}" class="dropdown-toggle">
<div class="">
<i class="fa-solid fa-list-check"></i>
<span>Courier Master</span>
</div>
</a>
</li>



                <li class="menu @routeis('active.slebs') active @endrouteis">
            <a href="{{ route('active.slebs.add') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-tags"></i>
                    <span>Active Slebs</span>
                </div>
            </a>
        </li>



        <li class="menu @routeis('beyond-work') active @endrouteis">
            <a href="{{ route('beyond-work') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-champagne-glasses"></i>

                    <span>Beyond Work</span>
                </div>
            </a>
        </li>

        <li class="menu @routeis('landing-brands') active @endrouteis">
            <a href="{{ route('landing-brands') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-globe"></i>
                    <span>Landing Brand</span>
                </div>
            </a>
        </li>

        <li class="menu @routeis('team-spirit') active @endrouteis">
            <a href="{{ route('team-spirit') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-handshake"></i>
                    <span>Team Spirit</span>
                </div>
            </a>
        </li>

        <li class="menu @routeis('weight.dispatching.index') active @endrouteis">
            <a href="{{ route('weight.dispatching.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Weight Dispatching</span>
                </div>
            </a>
        </li>


        <li class="menu @routeis('codremittance.index') active @endrouteis">
            <a href="{{ route('codremittance.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-id-card"></i>
                    <span>COD Remittance</span>
                </div>
            </a>
        </li>




    <li class="menu @routeis('negative-balance.index') active @endrouteis">
            <a href="{{ route('negative-balance.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Negative Balance</span>
                </div>
            </a>
        </li>


        {{-- <li class="menu @routeis('kyc-manage') active @endrouteis">
            <a href="{{ route('profile-manage') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Profile</span>
                </div>
            </a>
        </li> --}}


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
                <li class="@routeis('careers-details') active @endrouteis">
                    <a href="{{ route('careers-details') }}">Career Details</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([104]))
        <li class="menu @routeis('sliders,testimonials,cms,faq,enquiries,admin-banners') active @endrouteis">
            <a href="#static_content" data-bs-toggle="collapse"
                aria-expanded="{{ Helper::routeis('sliders,testimonials,cms,faq,enquiries,admin-banners') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa-sharp fa-solid fa-photo-film"></i>
                    <span>Content</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('sliders,testimonials,cms,faq,enquiries,admin-banners') show @endrouteis"
                id="static_content" data-bs-parent="#accordionExample">
                @if(Helper::userCan(104))
                <li class="@routeis('testimonial') active @endrouteis">
                    <a href="{{ route('testimonial') }}">Testimonial</a>
                </li>
                @endif


                @if(Helper::userCan(104))
                <li class="@routeis('careers') active @endrouteis">
                    <a href="{{ route('careers') }}">Career</a>
                </li>
                @endif

                @if(Helper::userCan(104))
                <li class="@routeis('privacy-policy') active @endrouteis">
                    <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
                </li>
                @endif
                @if(Helper::userCan(104))
                <li class="@routeis('refund-policy') active @endrouteis">
                    <a href="{{ route('refund-policy') }}">Refund Policy</a>
                </li>
                @endif
                @if(Helper::userCan(104))
                <li class="@routeis('terms-and-condition') active @endrouteis">
                    <a href="{{ route('terms-and-condition') }}">Terms And Condition</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([105,106]))
        <li class="menu @routeis('states,cities') active @endrouteis">
            <a href="#location_content" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('states,cities') }}"
                class="dropdown-toggle">
                <div class="">
                    <i class="fa-duotone fa-location-dot"></i>
                    <span>Location</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('states,cities') show @endrouteis" id="location_content"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(105))
                <li class="@routeis('states') active @endrouteis">
                    <a class="nav-link" href="{{ route('states') }}">States</a>
                </li>
                @endif

                @if(Helper::userCan(106))
                <li class="@routeis('cities') active @endrouteis">
                    <a class="nav-link" href="{{ route('cities') }}">Cities</a>
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
