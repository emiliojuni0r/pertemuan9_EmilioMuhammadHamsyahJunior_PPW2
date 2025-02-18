<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class LoginRegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except([
            'logout', 'dashboard'
        ]);
    }

    public function register() {
        return view('auth.register');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|email|max:250|unique:users',
            'password' => 'required|min:8|confirmed',
            'photo' => 'image|nullable|max:1999'
        ]);

        if($request->hasFile('photo')){
            $filenameWithExt = $request->file('photo')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $filenameSimpan = $filename . '_' . time() . $extension;
            $path = $request->file('photo')->storeAs('photos', $filenameSimpan);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'photo' => $path    
        ]);

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'subject' => 'New User has been registed',
            'body' => "Pengguna baru telah terdaftar.\nNama: {$user->name}\nEmail: {$user->email}"
        ];
        dispatch(new \App\Jobs\SendEmailJob($data));

        $credentials = $request->only('email', 'password');
        Auth::attempt($credentials);
        $request->session()->regenerate();
        return redirect()->route('dashboard')->withSuccess('you have succesfully registered & logged in!');
    }

    public function login(){
        return view('auth.login');
    }

    public function authenticate(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard')->with('success','you have succesfully logged in!');
        }

        return back()->withErrors([
            'email' => 'your provided credentials do not match in our records.',
        ])->onlyInput('email');
    }


    public function dashboard(){
        if(Auth::check()){
            return view('auth.dashboard');
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Please login to access the dashboard.',
        ])->onlyInput('email');
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->withSuccess('You have logged out succesfully');
    }
}
