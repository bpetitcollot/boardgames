import './Demo.css'
import Game from "./components/Solitaire/Solitaire.tsx";
import {useState} from "react";

export default function Demo() {
    const [actions, setActions] = useState<string>('');
    const postAction = async (action: string) => {
        const newActions = actions + '-' + action;
        setActions(newActions);
        return {actions: newActions, isFinished: false};
    }
  return (
    <>
        <Game actionsString="" postAction={postAction} />
    </>
  )
}
