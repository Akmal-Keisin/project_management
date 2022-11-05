@extends('layouts.main')
@section('title', 'Admin Create')
@section('main')
 <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h3 class="page-title text-truncate text-dark font-weight-medium mb-1">Create User</h3>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ url('user') }}">User</a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ url('user/create') }}">Create</a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">
        <!-- *************************************************************** -->
        <!-- Start Top Leader Table -->
        <!-- *************************************************************** -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if($msg = Session::get('failed'))
                            <div class="alert alert-danger">
                                {{ $msg }}
                            </div>
                        @endif
                        <div class="align-items-center mb-4">
                            <form action="{{ url('user') }}" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="name" class="form-label">User Name :</label>
                                    <input type="text" name="name" class="form-control" id="name" placeholder="Some User">
                                    @if($errors->has('name')) <span class="text-danger">{{ $errors->first('name') }}</span> @endif
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">user Email :</label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="someuser@gmail.com">
                                    @if($errors->has('email')) <span class="text-danger">{{ $errors->first('email') }}</span> @endif
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">User Password :</label>
                                    <input type="password" name="password" class="form-control" id="password" placeholder="Your secret password">
                                    @if($errors->has('password')) <span class="text-danger">{{ $errors->first('password') }}</span> @endif
                                </div>
                                <div class="mb-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- *************************************************************** -->
        <!-- End Top Leader Table -->
        <!-- *************************************************************** -->
    </div>
    <!-- ============================================================== -->
    <!-- End Container fluid  -->
    <!-- ============================================================== --
@endsection