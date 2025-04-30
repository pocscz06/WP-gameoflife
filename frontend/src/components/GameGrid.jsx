import React, { useState, useEffect, useCallback, useRef } from "react";
import "../styles/GameGrid.css";

const GameGrid = ({
  gridSize = { rows: 25, cols: 25 },
  onGenerationChange,
  onPopulationChange,
  onStart,
  onStop,
  onReset,
  onPatternSelect,
}) => {
  const createEmptyGrid = () => {
    return Array(gridSize.rows)
      .fill()
      .map(() => Array(gridSize.cols).fill(false));
  };

  const [grid, setGrid] = useState(() => createEmptyGrid());

  const [running, setRunning] = useState(false);
  const [generation, setGeneration] = useState(0);
  const [speed, setSpeed] = useState(200);
  const [patternUsed, setPatternUsed] = useState("");

  const runningRef = useRef(running);
  runningRef.current = running;

  const countPopulation = useCallback(() => {
    return grid.reduce(
      (count, row) =>
        count + row.reduce((sum, cell) => sum + (cell ? 1 : 0), 0),
      0
    );
  }, [grid]);

  useEffect(() => {
    if (onGenerationChange) onGenerationChange(generation);
    if (onPopulationChange) onPopulationChange(countPopulation());
  }, [
    generation,
    grid,
    onGenerationChange,
    onPopulationChange,
    countPopulation,
  ]);

  const toggleCellState = (rowIndex, colIndex) => {
    const newGrid = grid.map((row, i) =>
      i === rowIndex
        ? row.map((cell, j) => (j === colIndex ? !cell : cell))
        : row
    );
    setGrid(newGrid);
  };

  const countNeighbors = useCallback(
    (grid, row, col) => {
      let count = 0;

      for (let i = -1; i <= 1; i++) {
        for (let j = -1; j <= 1; j++) {
          if (i === 0 && j === 0) continue;

          const r = (row + i + gridSize.rows) % gridSize.rows;
          const c = (col + j + gridSize.cols) % gridSize.cols;

          if (grid[r][c]) count++;
        }
      }

      return count;
    },
    [gridSize.rows, gridSize.cols]
  );

  const calculateNextGeneration = useCallback(() => {
    setGrid((currentGrid) => {
      return currentGrid.map((row, rowIndex) =>
        row.map((cell, colIndex) => {
          const neighbors = countNeighbors(currentGrid, rowIndex, colIndex);

          if (cell) {
            if (neighbors < 2) return false;

            if (neighbors > 3) return false;

            return true;
          } else {
            return neighbors === 3;
          }
        })
      );
    });

    setGeneration((gen) => gen + 1);
  }, [countNeighbors]);

  useEffect(() => {
    if (!running) return;

    const simulationInterval = setInterval(() => {
      calculateNextGeneration();
    }, speed);

    return () => clearInterval(simulationInterval);
  }, [speed, calculateNextGeneration, running]);

  const clearGrid = () => {
    setGrid(createEmptyGrid());
    setGeneration(0);
  };

  const resetSimulation = () => {
    setRunning(false);
    clearGrid();
    if (onReset) {
      onReset();
    }
  };

  const toggleSimulation = () => {
    const newRunningState = !running;
    setRunning(newRunningState);

    if (newRunningState && onStart) {
      onStart(patternUsed);
    } else if (!newRunningState && onStop) {
      onStop();
    }
  };

  const stepOneGeneration = () => {
    calculateNextGeneration();
  };

  const step23Generations = () => {
    for (let i = 0; i < 23; i++) {
      setTimeout(() => calculateNextGeneration(), i * 50);
    }
  };

  const loadPattern = (pattern) => {
    clearGrid();
    let newGrid = createEmptyGrid();

    setPatternUsed(pattern);

    if (onPatternSelect) {
      onPatternSelect(pattern);
    }

    const centerRow = Math.floor(gridSize.rows / 2);
    const centerCol = Math.floor(gridSize.cols / 2);

    switch (pattern) {
      case "block": {
        newGrid[centerRow][centerCol] = true;
        newGrid[centerRow][centerCol + 1] = true;
        newGrid[centerRow + 1][centerCol] = true;
        newGrid[centerRow + 1][centerCol + 1] = true;
        break;
      }
      case "blinker": {
        newGrid[centerRow - 1][centerCol] = true;
        newGrid[centerRow][centerCol] = true;
        newGrid[centerRow + 1][centerCol] = true;
        break;
      }
      case "beacon": {
        newGrid[centerRow - 1][centerCol - 1] = true;
        newGrid[centerRow - 1][centerCol] = true;
        newGrid[centerRow][centerCol - 1] = true;
        newGrid[centerRow][centerCol] = true;
        newGrid[centerRow + 1][centerCol + 1] = true;
        newGrid[centerRow + 1][centerCol + 2] = true;
        newGrid[centerRow + 2][centerCol + 1] = true;
        newGrid[centerRow + 2][centerCol + 2] = true;
        break;
      }
      default:
        break;
    }

    setGrid(newGrid);
  };

  return (
    <div className="game-container">
      <div
        className="grid-container"
        style={{
          display: "grid",
          gridTemplateColumns: `repeat(${gridSize.cols}, 20px)`,
        }}
      >
        {grid.map((row, rowIndex) =>
          row.map((cell, colIndex) => (
            <div
              key={`${rowIndex}-${colIndex}`}
              className={`cell ${cell ? "alive" : "dead"}`}
              onClick={() => toggleCellState(rowIndex, colIndex)}
            />
          ))
        )}
      </div>

      <div className="controls">
        <button onClick={toggleSimulation}>{running ? "Stop" : "Start"}</button>
        <button onClick={stepOneGeneration}>Next Generation</button>
        <button onClick={step23Generations}>+23 Generations</button>
        <button onClick={resetSimulation}>Reset</button>

        <div className="pattern-selector">
          <label>Load Pattern: </label>
          <select onChange={(e) => loadPattern(e.target.value)}>
            <option value="">Select Pattern</option>
            <option value="block">Block</option>
            <option value="blinker">Blinker</option>
            <option value="beacon">Beacon</option>
          </select>
        </div>

        <div className="speed-control">
          <label>Speed: </label>
          <input
            type="range"
            min="50"
            max="500"
            step="50"
            value={speed}
            onChange={(e) => setSpeed(Number(e.target.value))}
          />
          <span>{speed}ms</span>
        </div>
      </div>
    </div>
  );
};

export default GameGrid;
