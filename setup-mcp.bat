@echo off
echo =================================================
echo   KAIH MCP - Quick Setup Script
echo =================================================
echo.

echo Step 1: Check Node.js installation...
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed!
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)
echo ✓ Node.js is installed

echo.
echo Step 2: Install MCP Server dependencies...
cd mcp-server
call npm install
if %errorlevel% neq 0 (
    echo ERROR: Failed to install server dependencies!
    cd ..
    pause
    exit /b 1
)
echo ✓ Server dependencies installed
cd ..

echo.
echo Step 3: Build MCP Server...
cd mcp-server
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: Failed to build server!
    cd ..
    pause
    exit /b 1
)
echo ✓ Server built successfully
cd ..

echo.
echo Step 4: Install MCP Client dependencies...
cd mcp-client
call npm install
if %errorlevel% neq 0 (
    echo ERROR: Failed to install client dependencies!
    cd ..
    pause
    exit /b 1
)
echo ✓ Client dependencies installed
cd ..

echo.
echo Step 5: Build MCP Client...
cd mcp-client
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: Failed to build client!
    cd ..
    pause
    exit /b 1
)
echo ✓ Client built successfully
cd ..

echo.
echo =================================================
echo   ✓ Setup Complete!
echo =================================================
echo.
echo Next steps:
echo.
echo 1. To start the MCP Server:
echo    cd mcp-server
echo    npm start
echo.
echo 2. To run the Interactive Client (in another terminal):
echo    cd mcp-client
echo    npm start
echo.
echo 3. For more information:
echo    - Read mcp-server/README.md
echo    - Read MCP_INTEGRATION_GUIDE.md
echo.
pause
