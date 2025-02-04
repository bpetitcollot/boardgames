'use client'

import {getCsrfToken} from "next-auth/react";
import {Button, Input} from "@chakra-ui/react";
import {Field} from "@/components/ui/field";
import {useEffect, useState} from "react";

export default function SigninForm() {
    const [csrfToken, setCsrfToken] = useState<string>('');
    useEffect(() => {
        async function getToken(){
            const token = await getCsrfToken() as string;
            setCsrfToken(token);
        }
        getToken();
    }, [])
    return (
        <form method="post" action="/api/auth/callback/credentials">
            <input name="csrfToken" type="hidden" defaultValue={csrfToken} />
            <Field label="Email">
                <Input name="username" />
            </Field>
            <Field label="Mot de passe">
                <Input name="password" type="password" />
            </Field>
            <Button type="submit">Envoyer</Button>
        </form>
    );
}
