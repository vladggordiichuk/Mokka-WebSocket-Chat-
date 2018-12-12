<coverlette>
    <login>
        <div id="login">
            <h1>Login</h1>
            <input name="username" placeholder="Username" />
            <input name="password" type="password" placeholder="Password" />
            <btn login>Login</btn>
        </div>
        <div id="register">
            <h1>Sign Up</h1>
            <div id="registerUploadPhoto" style="display: none;"></div>
            <script>
            registerPhotoFrame = $('#registerUploadPhoto').croppie({
                viewport: {
                    width: 102,
                    height: 102,
                    type: 'circle'
                }
            });
            </script>
            <input type="text" name="first_name" placeholder="First Name" />
            <input type="text" name="last_name" placeholder="Last Name" />
            <input type="text" name="username" placeholder="Username" />
            <input type="password" name="password" placeholder="Password" />
            <btn back style="display: none;">Back</btn>
            <btn photo>Upload Photo<input type="file" accept="image/*" /></btn>
        </div>
    </login>
</coverlette>