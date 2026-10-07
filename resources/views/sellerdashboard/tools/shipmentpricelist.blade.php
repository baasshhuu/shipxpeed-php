@extends('layouts.sellerdash')

@section('content')
    <div class="pc-content mt-5">





        <div class=" mt-2">
            <div class="bg-white rounded shadow-sm p-3">



                <!-- Offcanvas Sidebar (Filters) -->
                <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Filters</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>Use filters to refine your results.</p>
                        <button class="btn btn-secondary dropdown-toggle w-100" data-bs-toggle="dropdown">Select
                            Option</button>
                        <ul class="dropdown-menu w-100">
                            <li><a class="dropdown-item" href="#">Option 1</a></li>
                            <li><a class="dropdown-item" href="#">Option 2</a></li>
                            <li><a class="dropdown-item" href="#">Option 3</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Responsive Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Order Date</th>
                                <th>Order Details</th>
                                <th>Product Details</th>
                                <th>Package Details</th>
                                <th>Courier Mode</th>
                                <th>Weight</th>
                                <th>Zone A</th>
                                <th>Zone B</th>
                                <th>Zone C</th>
                                <th>Zone D</th>
                                <th>Zone E</th>
                                <th>COD Charges / COD %</th>
                                <th>Other Charges</th>
                                <th>Shipping</th>
                                <th>Pickup Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2025-03-18</td>
                                <td>#123456</td>
                                <td>iPhone 14 Cover</td>
                                <td>Custom Print</td>
                                <td><i class="fas fa-truck"></i> Express</td>
                                <td>500g</td>
                                <td>$5</td>
                                <td>$7</td>
                                <td>$9</td>
                                <td>$11</td>
                                <td>$13</td>
                                <td>2%</td>
                                <td>$1.50</td>
                                <td>UPS Express<br><span class="text-muted">Forward</span></td>
                                <td>New York, USA</td>
                                <td><button class="btn btn-primary btn-sm">View</button></td>
                            </tr>
                            <tr>
                                <td>2025-03-17</td>
                                <td>#654321</td>
                                <td>Samsung S22 Cover</td>
                                <td>Matte Finish</td>
                                <td><i class="fas fa-plane"></i> Air</td>
                                <td>750g</td>
                                <td>$6</td>
                                <td>$8</td>
                                <td>$10</td>
                                <td>$12</td>
                                <td>$14</td>
                                <td>3%</td>
                                <td>$2.00</td>
                                <td>DHL<br><span class="text-muted">Forward</span></td>
                                <td>Los Angeles, USA</td>
                                <td><button class="btn btn-primary btn-sm">View</button></td>
                            </tr>
                            <tr>
                                <td>2025-03-16</td>
                                <td>#789012</td>
                                <td>Google Pixel 7 Case</td>
                                <td>Glossy Print</td>
                                <td><i class="fas fa-ship"></i> Standard</td>
                                <td>1kg</td>
                                <td>$7</td>
                                <td>$9</td>
                                <td>$11</td>
                                <td>$13</td>
                                <td>$15</td>
                                <td>4%</td>
                                <td>$2.50</td>
                                <td>FedEx<br><span class="text-muted">Forward</span></td>
                                <td>Chicago, USA</td>
                                <td><button class="btn btn-primary btn-sm">View</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                <!-- Pagination -->
                <nav class="d-flex justify-content-end mt-3">
                    <ul class="pagination">
                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>

    </div>
@endsection
