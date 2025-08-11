import React, { useState } from 'react';

function App() {
  const [count, setCount] = useState(0);

  return (
    <div className="container">
      <h1>Welcome to [{[codespaceName]}]</h1>
      <div className="app">
        <p>This is your new React codespace!</p>
        <p>Edit the files to start building your application.</p>
        
        <div className="counter">
          <button onClick={() => setCount(count - 1)}>-</button>
          <span>Count: {count}</span>
          <button onClick={() => setCount(count + 1)}>+</button>
        </div>
      </div>
    </div>
  );
}

export default App;
