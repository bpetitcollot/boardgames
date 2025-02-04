'use client'

import {Box, Button, Heading, Input} from "@chakra-ui/react";
import {SubmitHandler, useForm} from "react-hook-form";
import {Field} from "@/components/ui/field";
import {toaster} from "@/components/ui/toaster";

type RegisterData = {
    email: string
    username: string
    plainPassword: string
    repeatPassword: string
}
export default function Register() {
    const {register, handleSubmit, formState: {errors}, setError} = useForm<RegisterData>();
    const onSubmit: SubmitHandler<RegisterData> = async data => {
        if (data.plainPassword !== data.repeatPassword){
            setError('repeatPassword', {type: 'repeatedField', message: 'Les mots de passe doivent être identiques.'})
            return;
        }
        const result = await fetch('http://api.boardgames.localhost/register', {
            method: 'POST',
            body: JSON.stringify({
                email: data.email,
                plainPassword: data.plainPassword,
                username: data.username,
            })
        });
        if (result.status === 400){
            const errorsData = await result.json();
            for (let error of errorsData.errors.violations){
                setError(error.propertyPath, {type: 'api', message: error.title});
            }
            toaster.create({
                title: 'Erreur',
                description: 'Le formulaire comporte des erreurs.',
            });
            return;
        }
        if (!result.ok){
            return;
        }

        const registerData: {
        user: {
            username: string
        }} = await result.json();
        toaster.create({
            title: 'Inscription terminée',
            description: `Vous êtes maintenant enregistré en tant que ${registerData.user.username}.`,
        });
    }

    return (
      <>
        <Heading textAlign="center" mt={5}>Inscription</Heading>
          <Box maxWidth="20rem" margin="auto">
              <form onSubmit={handleSubmit(onSubmit)}>
                  <Field label="Email" invalid={!!errors.email} errorText={errors.email?.message}>
                      <Input {...register('email', {required: "L'email est obligatoire"})} />
                  </Field>
                  <Field label="Nom d'utilisateur" invalid={!!errors.username} errorText={errors.username?.message}>
                      <Input {...register('username', {required: "Le nom d'utilisateur est obligatoire"})} />
                  </Field>
                  <Field label="Mot de passe" invalid={!!errors.plainPassword} errorText={errors.plainPassword?.message}>
                      <Input type="password" {...register('plainPassword', {required: "Le mot de passe est obligatoire"})} />
                  </Field>
                  <Field label="Mot de passe" invalid={!!errors.repeatPassword} errorText={errors.repeatPassword?.message}>
                      <Input type="password" {...register('repeatPassword', {required: "Le mot de passe est obligatoire"})} />
                  </Field>
                  <Button type="submit">Envoyer</Button>
              </form>
          </Box>
      </>
    );
}
