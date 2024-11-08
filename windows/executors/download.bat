@echo off
setlocal EnableDelayedExpansion
cd "%~dp0"
if "%1"=="quick" (
	call ..\src\variables.bat no_migrate
) else (
	call ..\src\variables.bat
)
cd "%~dp0"
if "%1"=="quick" (
	if "%2"=="" (
		set option=2
	) else (
		set option=%2
	)
	goto quick_main
)


REM Define an array to store the model types and their names
set "names[1]=Whisper Model"
set "names[2]=TAIDE Model"
set "names[3]=Stable Diffusion Model"
set "names[4]=Diarization Model"
set "names[5]=Llama3.1 Model"
set "names[6]=Gemma2 Model"
REM set "names[4]=Embedding Model"
REM set "names[5]=GGUF Model"
REM set "names[6]=HuggingFace Model"
set "names[7]=Exit"

REM Define an array to store the model types and their names
set "models[1]=whisper"
set "models[2]=taide"
set "models[3]=stable_diffusion"
set "models[4]=diarization"
set "models[5]=llama"
set "models[6]=gemma"
REM set "models[4]=embedding_model"
REM set "models[5]=gguf_model"
REM set "models[6]=huggingface"
set "models[7]=exit"
:main
cls
echo Now in: "%cd%"

echo Download Model:

for %%a in (1 2 3 4 5 6 7) do (
    echo %%a - !names[%%a]!
    if "%%a" == "6" (
        echo ------------
    )
)
set /p "option=Enter the option number (1-7): "
if not defined models[%option%] (
    echo Invalid option. Please try again.
    goto main
)
:quick_main
set "EXECUTOR_TYPE=!models[%option%]!"

if "%option%"=="1" (
    :function1
    set userInput=n
    set /p "userInput=�n�U�� Whisper Medium �ҫ��� (�� 1.4GB)�H [y/N] "
    
    if /I "!userInput!"=="y" (
    	echo ���b�U���ҫ�...
		set python_exe=..\packages\%python_folder%\python.exe
		if exist "!python_exe!" (
			!python_exe! ../../src/executor/speech_recognition/download_model.py
		) else (
			echo �ʤָ��ɮ� !python_exe! �A�Х����槹��build.bat�I
		)
		echo �U�������I
	) else (
		echo �N���|�U���Ӽҫ�
	)
    pause
) else if "%option%"=="2" (
    :function2
    set userInput=n
    set /p "userInput=�n�U�� Llama3-TAIDE-LX-8B-Chat-Alpha1.Q4_K_M �� GGUF �ҫ��� (�� 4.7GB)�H [y/N] "
    
    if /I "!userInput!"=="y" (
    	echo ���b�U���ҫ�...
    	curl -L -o "taide/taide-8b-a.3-q4_k_m.gguf" https://huggingface.co/QuantFactory/Llama3-TAIDE-LX-8B-Chat-Alpha1-GGUF/resolve/main/Llama3-TAIDE-LX-8B-Chat-Alpha1.Q4_K_M.gguf
		echo �U�������I
	) else (
		echo �N���|�U���Ӽҫ�
	)
    pause
) else if "%option%"=="3" (
    :function3
    set userInput=n
    set /p "userInput=�n�U�� Stable diffusion 2 �ҫ��� (�� 4.8GB)�H [y/N] "
    
    if /I "!userInput!"=="y" (
    	echo ���b�U���ҫ�...
		set python_exe=..\packages\%python_folder%\python.exe

		if exist "!python_exe!" (
			!python_exe! ../../src/executor/image_generation/download_model.py
		) else (
			echo �ʤָ��ɮ� !python_exe! �A�Х����槹��build.bat�I
		)
		echo �U�������I
	) else (
		echo �N���|�U���Ӽҫ�
	)
    pause
) else if "%option%"=="4" (
    :function4
    set userInput=n
    set /p "userInput=�n�U�� pyannote/speaker-diarization-3.1 �ҫ��� (�� 31.2MB)�H [y/N] "
    
    if /I "!userInput!"=="y" (
    	echo ���b�U���ҫ�...
		set python_exe=..\packages\%python_folder%\python.exe

		if exist "!python_exe!" (
			!python_exe! ../../src/executor/speech_recognition/download_model.py --diarizer
		) else (
			echo �ʤָ��ɮ� !python_exe! �A�Х����槹��build.bat�I
		)
		echo �U�������I
	) else (
		echo �N���|�U���Ӽҫ�
	)
    pause
) else if "%option%"=="5" (
    :function5
    set userInput=n
    set /p "userInput=�n�U�� Llama3.1-8B.Q4_K_M �� GGUF �ҫ��� (�� 4.7GB)�H [y/N] "
    
    if /I "!userInput!"=="y" (
    	echo ���b�U���ҫ�...
    	curl -L -o "llama3_1/llama3_1-8b-q4_k_m.gguf" https://huggingface.co/chatpdflocal/llama3.1-8b-gguf/resolve/main/ggml-model-Q4_K_M.gguf
		echo �U�������I
	) else (
		echo �N���|�U���Ӽҫ�
	)
    pause
) else if "%option%"=="6" (
    :function5
    set userInput=n
    set /p "userInput=�n�U��  gemma-2-2b-it-Q8_0 �� GGUF �ҫ��� (�� 2.78GB)�H [y/N] "
    
    if /I "!userInput!"=="y" (
    	echo ���b�U���ҫ�...
    	curl -L -o "gemma2/gemma-2-2b-it-Q8_0.gguf" https://huggingface.co/lmstudio-community/gemma-2-2b-it-GGUF/resolve/main/gemma-2-2b-it-Q8_0.gguf
		echo �U�������I
	) else (
		echo �N���|�U���Ӽҫ�
	)
    pause
) else if "%option%"=="7" (
    exit
)
if "%1"=="quick" (
	exit
)

goto main
