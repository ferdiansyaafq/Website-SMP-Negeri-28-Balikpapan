#!/bin/bash

echo "================================================="
echo "  KAIH MCP - Quick Setup Script"
echo "================================================="
echo ""

# Check Node.js
echo "Step 1: Check Node.js installation..."
if ! command -v node &> /dev/null; then
    echo "ERROR: Node.js is not installed!"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi
echo "✓ Node.js is installed: $(node --version)"

# Install server
echo ""
echo "Step 2: Install MCP Server dependencies..."
cd mcp-server
npm install
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install server dependencies!"
    cd ..
    exit 1
fi
echo "✓ Server dependencies installed"
cd ..

# Build server
echo ""
echo "Step 3: Build MCP Server..."
cd mcp-server
npm run build
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to build server!"
    cd ..
    exit 1
fi
echo "✓ Server built successfully"
cd ..

# Install client
echo ""
echo "Step 4: Install MCP Client dependencies..."
cd mcp-client
npm install
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install client dependencies!"
    cd ..
    exit 1
fi
echo "✓ Client dependencies installed"
cd ..

# Build client
echo ""
echo "Step 5: Build MCP Client..."
cd mcp-client
npm run build
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to build client!"
    cd ..
    exit 1
fi
echo "✓ Client built successfully"
cd ..

echo ""
echo "================================================="
echo "  ✓ Setup Complete!"
echo "================================================="
echo ""
echo "Next steps:"
echo ""
echo "1. To start the MCP Server:"
echo "   cd mcp-server"
echo "   npm start"
echo ""
echo "2. To run the Interactive Client (in another terminal):"
echo "   cd mcp-client"
echo "   npm start"
echo ""
echo "3. For more information:"
echo "   - Read mcp-server/README.md"
echo "   - Read MCP_INTEGRATION_GUIDE.md"
echo ""
