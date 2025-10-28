<div class="min-h-screen flex items-center justify-center bg-base-200">
    <div class="card w-96 bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold text-center justify-center mb-4">
                Login to Dashboard
            </h2>

            <form wire:submit="login">
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <input
                        type="email"
                        wire:model="email"
                        placeholder="Enter your email"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        required
                    />
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control w-full mt-4">
                    <label class="label">
                        <span class="label-text">Password</span>
                    </label>
                    <input
                        type="password"
                        wire:model="password"
                        placeholder="Enter your password"
                        class="input input-bordered w-full @error('password') input-error @enderror"
                        required
                    />
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mt-4">
                    <label class="label cursor-pointer justify-start gap-2">
                        <input type="checkbox" wire:model="remember" class="checkbox checkbox-sm" />
                        <span class="label-text">Remember me</span>
                    </label>
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
