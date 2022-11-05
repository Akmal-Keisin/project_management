@extends('layouts.main')
@section('title', 'Project Create')
@section('main')
 <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h3 class="page-title text-truncate text-dark font-weight-medium mb-1">Create Project</h3>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ url('project') }}">Project</a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ url('project/create') }}">Create</a>
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
                            <form action="{{ url('project') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="name" class="form-label">Project Name :</label>
                                    <input type="text" name="name" class="form-control" id="name" placeholder="Project Management System">
                                    @if($errors->has('name')) <span class="text-danger">{{ $errors->first('name') }}</span> @endif
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Project Description :</label>
                                    <textarea name="description" id="description" cols="30" rows="10" class="form-control" placeholder="App to manage some project with other people"></textarea>
                                    @if($errors->has('description')) <span class="text-danger">{{ $errors->first('description') }}</span> @endif
                                </div>
                                <div class="mb-3">
                                    <label for="image" class="form-label">Image :</label>
                                    <input type="file" name="image" id="image" class="form-control">
                                </div>
                                <div class="mb-3 d-flex">
                                    <div class="mr-3">
                                        <label for="date_start" class="form-label">Date Start :</label>
                                        <input type="date" name="date_start" id="date_start" class="form-control">
                                        @if($errors->has('date_start')) <span class="text-danger">{{ $errors->first('date_start') }}</span> @endif
                                    </div>
                                    <div>
                                        <label for="deadline" class="form-label">Deadline :</label>
                                        <input type="date" name="deadline" id="deadline" class="form-control">
                                        @if($errors->has('deadline')) <span class="text-danger">{{ $errors->first('deadline') }}</span> @endif
                                    </div>                                    
                                </div>
                                <div class="mb-3">
                                    <label for="created_by" class="form-label">Project Manager</label>
                                    <input type="number" name="project_manager" class="form-control" placeholder="Project Manager Id">
                                    @if($errors->has('project_manager')) <span class="text-danger">{{ $errors->first('project_manager') }}</span> @endif
                                </div>
                                <div class="mb-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Add!</button>
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