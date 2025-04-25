@echo off
cd "%~dp0"
setlocal enabledelayedexpansion
rem Initialize the CUDA version variable
set "version=cpu"

rem Check if the version parameter is provided
if not "%1"=="" (
    set "set_version=%1"
)

if "%set_version%"=="" (
	rem Check if nvcc (NVIDIA CUDA Compiler) is available
	nvcc --version >NUL
	if %errorlevel% neq 0 (
		echo CUDA is not installed
	) else (
		for /f "tokens=6" %%v in ('nvcc --version ^| findstr /i "release"') do (
			set "cuda_version=%%v"
		)
		rem Parse the version number
		for /f "tokens=1,2,3 delims=." %%a in ("!cuda_version!") do (
			set "major=%%a"
			set "minor=%%b"
			set "patch=%%c"
		)
		rem Set the variable based on the CUDA version
		if !major:~1! gtr 12 (
			set "version=12.3"
		) else if !major:~1! equ 12 (
			if !minor! equ 3 (
				set "version=12.3"
			) else if !minor! gtr 3 (
				set "version=12.3"
			) else if !minor! equ 2 (
				set "version=12.2"
			) else if !minor! equ 1 (
				set "version=12.1"
			)
		) else if !major:~1! equ 11 (
			if !minor! equ 8 (
				set "version=11.8"
			)
		)
	)
) else (
	set "version=%set_version%"
)
echo Picked version: !version!
xcopy /s /e /i /Y version_patch\!version!\* ..\..\
endlocal