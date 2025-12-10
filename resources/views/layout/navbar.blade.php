
<nav class="p-2 p-md-0 bg-neutral-primary sticky w-full z-20 top-0 border-b border-default ">
  <div class="px-2 px-lg-3 lg:px-5 w-full flex flex-wrap items-center justify-between">
    <a href="{{ route('homepage') }}#home" class="flex items-center space-x-3 rtl:space-x-reverse order-1 md:order-1 text-decoration-none">
        <img src="{{ asset('images/hugo_perez_logo.png') }}" class="h-13" alt="HugoPerez Logo" />
        <span class="logo-title self-center text-xl text-heading font-semibold whitespace-nowrap lg:block hidden ">Health Center IMS</span>
    </a>
    <div class="order-2 flex flex-wrap">
      <div class=" flex items-center order-3">
          <button data-collapse-toggle="navbar-default" type="button" class=" order-2 inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-body rounded-base md:hidden hover:bg-neutral-secondary-soft hover:text-heading focus:outline-none focus:ring-2 focus:ring-neutral-tertiary" aria-controls="navbar-default" aria-expanded="false">
              <span class="sr-only">Open main menu</span>
              <i class="fa-solid fa-bars text-3xl mx-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"></i>
          </button>
          <a href="{{ route('login') }}" class="login-text text-white font-[Poppins] duration-500 px-6 py-2 mx-4  rounded order-1 text-decoration-none">Login</a>
      </div>
      <divid="navbar-default"
     class="transition-all duration-500 ease-in-out overflow-hidden
            max-h-0 md:max-h-none hidden md:block w-full md:w-auto flex md:flex-row order-2 align-center">
        <ul class="font-medium flex flex-wrap flex-col md:p-0 p-3  bg-neutral-secondary-soft md:flex-row  rtl:space-x-reverse  md:border-0 md:bg-neutral-primary">
          <li>
            <a href="{{ route('homepage') }}#home" class="fs-5 block py-2 px-2 px-lg-3 bg-brand rounded md:bg-transparent md:text-fg-brand md:p-0" aria-current="page">Home</a>
          </li>
          <li>
            <a href="{{ route('homepage') }}#about" class="fs-5 block py-2 px-2 px-lg-3 text-heading rounded hover:bg-neutral-tertiary md:hover:bg-transparent md:border-0 md:hover:text-fg-brand md:p-0 md:dark:hover:bg-transparent">About</a>
          </li>
          <li>
            <a href="{{ route('homepage') }}#services" class="fs-5 block py-2 px-2 px-lg-3 text-heading rounded hover:bg-neutral-tertiary md:hover:bg-transparent md:border-0 md:hover:text-fg-brand md:p-0 md:dark:hover:bg-transparent">Services</a>
          </li>
          <li>
            <a href="{{ route('homepage') }}#specialist" class="fs-5 block py-2 px-2 px-lg-3 text-heading rounded hover:bg-neutral-tertiary md:hover:bg-transparent md:border-0 md:hover:text-fg-brand md:p-0 md:dark:hover:bg-transparent">Specialist</a>
          </li>
          <li>
            <a href="{{ route('homepage') }}#faq" class="fs-5 block py-2 px-2 px-lg-3 text-heading rounded hover:bg-neutral-tertiary md:hover:bg-transparent md:border-0 md:hover:text-fg-brand md:p-0 md:dark:hover:bg-transparent">FAQs</a>
          </li>
          <li>
            <a href="{{ route('homepage') }}#events" class="fs-5 block py-2 px-2 px-lg-3 text-heading rounded hover:bg-neutral-tertiary md:hover:bg-transparent md:border-0 md:hover:text-fg-brand md:p-0 md:dark:hover:bg-transparent">Events</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
