import {useMemo, useState} from "react";

interface Action{
    from: { x: number, y: number },
    to: { x: number, y: number },
}

interface GameState{
    actions: string,
    isFinished: boolean,
}

const DEFAULT_GRID = [
    [0, 0, 0, 2, 2, 2, 0, 0, 0],
    [0, 0, 0, 2, 2, 2, 0, 0, 0],
    [0, 0, 0, 2, 2, 2, 0, 0, 0],
    [2, 2, 2, 2, 2, 2, 2, 2, 2],
    [2, 2, 2, 2, 1, 2, 2, 2, 2],
    [2, 2, 2, 2, 2, 2, 2, 2, 2],
    [0, 0, 0, 2, 2, 2, 0, 0, 0],
    [0, 0, 0, 2, 2, 2, 0, 0, 0],
    [0, 0, 0, 2, 2, 2, 0, 0, 0],
];

function Grid({columns, children}: {columns: number, children: any}){
    return <div style={{display: 'grid', gridTemplateColumns: `repeat(${columns}, 1fr)`}}>{children}</div>;
}
function Slot({value, isActive, onClick}: {value: number, isActive: boolean, onClick: () => void}){
    return <div
        style={{
            width: '2em',
            height: '2em',
            borderRadius: '1em',
            backgroundColor: value == 0 ? 'none' : (value == 1 ? 'gray' : 'orange'),
            border: '1px solid ' + (isActive ? 'red' : 'transparent'),
        }}
        onClick={onClick}
    >
    </div>;
}

function isValid(grid: number[][], from: {x: number, y: number}, to: {x: number, y: number}){
    if (from.x === to.x && Math.abs(from.y - to.y) === 2){
        return grid[to.y][to.x] === 1 && grid[(from.y + to.y) / 2][from.x] === 2;
    }
    if (from.y === to.y && Math.abs(from.x - to.x) === 2){
        return grid[to.y][to.x] === 1 && grid[from.y][(from.x + to.x) / 2] === 2;
    }
    return false;
}

function serialize(action: Action): string
{
    return `${action.from.x},${action.from.y},${action.to.x},${action.to.y}`;
}
function deserialize(actionsString: string): Action[]
{
    return actionsString === '' ? [] : actionsString.split('-').map(actionString => {
        const [fromX, fromY, toX, toY] = actionString.split(',');
        return {from: {x: parseInt(fromX), y: parseInt(fromY)}, to: {x: parseInt(toX), y: parseInt(toY)}};
    });
}

function Solitaire({grid, isInteractive, onMove}: { grid: number[][], isInteractive: boolean, onMove: (action: Action) => void }){
    const [action, setAction] = useState<{from: { x:number, y:number }|null, to: { x:number, y:number }|null}>({from: null, to: null});
    return <Grid columns={grid[0].length}>
        {grid.map((row, y) => (
            row.map((slot, x) => (
                <Slot
                    key={`slot-${x}-${y}`}
                    value={slot}
                    isActive={isInteractive && action.from?.x === x && action.from?.y === y}
                    onClick={() => {
                        if (!isInteractive || !slot){
                            return;
                        }
                        if (!action.from && slot === 2){
                            setAction({...action, from: {x, y}});
                        } else if (action.from === null){
                            return;
                        } else if (action.from?.x === x && action.from?.y === y){
                            setAction({...action, from: null})
                        } else if (isValid(grid, action.from, {x, y})){
                            onMove({...action, to: {x, y}} as Action);
                            setAction({from: null, to: null});
                        }
                    }}
                />
            ))
        ))}
    </Grid>
}

function GameControl({
    currentAction,
    lastAction,
    onChange
}: {
    currentAction: number,
    lastAction: number,
    onChange: (action :number) => void
}){
    return <>
        <button
            onClick={() => {
                if (currentAction > 0) {
                    onChange(0);
                }
            }}
        >
            &lt;&lt;
        </button>
        <button
            onClick={() => {
                if (currentAction > 0) {
                    onChange(currentAction - 1);
                }
            }}
        >
            &lt;
        </button>
        {currentAction} / {lastAction}
        <button
            onClick={() => {
                if (currentAction < lastAction) {
                    onChange(currentAction + 1);
                }
            }}
        >
            &gt;
        </button>
        <button
            onClick={() => {
                if (currentAction < lastAction) {
                    onChange(lastAction);
                }
            }}
        >
            &gt;&gt;
        </button>
    </>
}

export default function Game({actionsString, postAction, onGameFinished}: {actionsString: string, postAction: (actionString: string) => Promise<GameState|null>, onGameFinished?: () => void}){
    const [actions, setActions] = useState<Action[]>(deserialize(actionsString));
    const [currentAction, setCurrentAction] = useState<number>(actions.length);
    const grid = useMemo<number[][]>(() => {
        return actions.slice(0, currentAction).reduce((currentGrid, action) => {
            if (currentGrid[action.from.y][action.from.x] !== 2
                || currentGrid[(action.from.y + action.to.y) / 2][(action.from.x + action.to.x) / 2] !== 2
                || currentGrid[action.to.y][action.to.x] !== 1
            ) {
                // throw exception
            }
            let newCurrentGrid = currentGrid.map(row => row.map(s => s));
            newCurrentGrid[action.from.y][action.from.x] = 1;
            newCurrentGrid[(action.from.y + action.to.y) / 2][(action.from.x + action.to.x) / 2] = 1;
            newCurrentGrid[action.to.y][action.to.x] = 2;
            return newCurrentGrid;
        }, DEFAULT_GRID);
    }, [actions, currentAction]);
    return <div>
        <GameControl
            currentAction={currentAction}
            lastAction={actions.length}
            onChange={newAction => {
                setCurrentAction(newAction);
            }}
        />
        <Solitaire
            grid={grid}
            isInteractive={currentAction === actions.length}
            onMove={async action => {
                const result = await postAction(serialize(action));
                if (result) {
                    const newActions = deserialize(result.actions);
                    setActions(newActions);
                    setCurrentAction(newActions.length);
                    if (result.isFinished && onGameFinished) {
                        onGameFinished();
                    }
                }
            }}
        />
    </div>
}