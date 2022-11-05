@extends('auth.layout')
@section('title', 'Login')
@section('main')
    <div class="text-center">
        <img src="{{ asset('images/logo.png') }}" class="w-25" alt="login image">
    </div>
    <h2 class="mt-3 text-center">Sign In</h2>
    <p class="text-center">Enter your email address and password to access admin panel.</p>
    <form class="mt-4" action="{{ url('auth/login') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <label class="text-dark" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="text"
                        placeholder="enter your email"/>
                    @if($errors->has('email')) <span class="text-danger">{{ $errors->first('email') }}</span> @endif
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-group">
                    <label class="text-dark" for="pwd">Password</label>
                    <input class="form-control" id="pwd" type="password"
                        placeholder="enter your password" name="password">
                    @if($errors->has('password')) <span class="text-danger">{{ $errors->first('password') }}</span> @endif
                </div>
            </div>
            <div class="col-lg-12 text-center">
                <button type="submit" class="btn btn-block btn-dark">Sign In</button>
            </div>
        </div>
    </form>
@endsection