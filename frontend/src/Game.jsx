import React, { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import GameGrid from "./components/GameGrid";
import GameStats from "./components/GameStats";
import "./styles/Game.css";

const Game = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [generation, setGeneration] = useState(0);
  const [population, setPopulation] = useState(0);
  const [sessionId, setSessionId] = useState(null);
  const [gameStarted, setGameStarted] = useState(false);
  const [patternUsed, setPatternUsed] = useState("");
  const [maxPopulation, setMaxPopulation] = useState(0);

  const sessionIdRef = useRef(null);
  const generationRef = useRef(0);
  const maxPopulationRef = useRef(0);
  const patternUsedRef = useRef("");
  const gameStartedRef = useRef(false);

  useEffect(() => {
    sessionIdRef.current = sessionId;
    generationRef.current = generation;
    maxPopulationRef.current = maxPopulation;
    patternUsedRef.current = patternUsed;
    gameStartedRef.current = gameStarted;
  }, [sessionId, generation, maxPopulation, patternUsed, gameStarted]);

  useEffect(() => {
    let updateInterval;

    if (gameStarted && sessionId && generation > 0) {
      updateInterval = setInterval(() => {
        fetch(
          `https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/session.php?id=${sessionId}`,
          {
            method: "PUT",
            credentials: "include",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              generations: generation,
              max_population: maxPopulation,
            }),
          }
        ).catch((error) => {
          console.error("Error updating game data:", error);
        });
      }, 5000);
    }

    return () => {
      if (updateInterval) {
        clearInterval(updateInterval);
      }
    };
  }, [gameStarted, sessionId, generation, maxPopulation]);

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        const response = await fetch(
          "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/user/check-session.php",
          {
            credentials: "include",
          }
        );
        const data = await response.json();

        if (data.logged_in) {
          setUser(data.user);
        } else {
          navigate("/login");
        }
      } catch (error) {
        console.error("Error checking login status:", error);
        navigate("/login");
      }
    };

    checkLoginStatus();
  }, [navigate]);

  useEffect(() => {
    let updateInterval;

    if (gameStarted && sessionId) {
      updateInterval = setInterval(() => {
        if (generationRef.current > 0) {
          updateSessionData(false);
        }
      }, 15000);
    }

    return () => {
      if (updateInterval) {
        clearInterval(updateInterval);
      }

      if (sessionIdRef.current && gameStartedRef.current) {
        updateSessionData(true);
      }
    };
  }, [gameStarted, sessionId]);

  const updateSessionData = async (endSession = false) => {
    if (!sessionIdRef.current) return;

    try {
      const url = `https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/session.php?id=${sessionIdRef.current}`;

      const body = {
        generations: generationRef.current,
        max_population: maxPopulationRef.current,
        pattern_used: patternUsedRef.current,
      };

      if (endSession) {
        body.end_session = true;
      }

      await fetch(url, {
        method: "PUT",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
      });

      if (endSession && gameStartedRef.current) {
        setGameStarted(false);
        setSessionId(null);
      }
    } catch (error) {
      console.error("Error updating session data:", error);
    }
  };

  const handleGameStart = async (pattern) => {
    if (!gameStarted) {
      try {
        const response = await fetch(
          "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/session.php",
          {
            method: "POST",
            credentials: "include",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ pattern }),
          }
        );

        const data = await response.json();

        if (data.success) {
          setSessionId(data.session_id);
          setGameStarted(true);
          setPatternUsed(pattern || "custom");
        }
      } catch (error) {
        console.error("Error starting game session:", error);
      }
    }
  };

  const handleGameEnd = async () => {
    if (gameStarted && sessionId) {
      await updateSessionData(true);
    }
  };

  const handlePatternSelect = (pattern) => {
    setPatternUsed(pattern || "custom");
    if (!gameStarted) {
      handleGameStart(pattern);
    }
  };

  useEffect(() => {
    if (population > maxPopulation) {
      setMaxPopulation(population);
    }
  }, [population, maxPopulation]);

  const handleGenerationChange = (newGeneration) => {
    setGeneration(newGeneration);
  };

  const handlePopulationChange = (newPopulation) => {
    setPopulation(newPopulation);
  };

  return (
    <div className="game-page">
      <h1>Conway's Game of Life</h1>

      {user && (
        <div className="user-info">
          <p>Welcome, {user.username}!</p>
        </div>
      )}

      <div className="game-layout">
        <GameStats
          generation={generation}
          population={population}
          maxPopulation={maxPopulation}
        />

        <GameGrid
          gridSize={{ rows: 30, cols: 30 }}
          onGenerationChange={handleGenerationChange}
          onPopulationChange={handlePopulationChange}
          onStart={(pattern) => handleGameStart(pattern)}
          onStop={handleGameEnd}
          onReset={handleGameEnd}
          onPatternSelect={handlePatternSelect}
          sessionId={sessionId}
        />
      </div>
    </div>
  );
};

export default Game;
